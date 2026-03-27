package org.example.me.mysqlcmd;

import com.google.gson.JsonArray;
import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import org.bukkit.Bukkit;
import org.bukkit.command.Command;
import org.bukkit.command.CommandSender;
import org.bukkit.plugin.java.JavaPlugin;
import org.bukkit.scheduler.BukkitRunnable;
import org.bukkit.scheduler.BukkitTask;

import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.List;
import java.util.concurrent.atomic.AtomicBoolean;
import java.util.concurrent.atomic.AtomicInteger;
import java.util.concurrent.atomic.AtomicLong;

public final class Mysqlcmd extends JavaPlugin {

    private BukkitTask pollingTask;
    private final AtomicBoolean isPolling = new AtomicBoolean(false);

    // Config cache
    private String apiUrl;
    private String serverId;
    private String apiSecret;
    private int    maxCommandsPerCycle;
    private long   pollingIntervalTicks;
    private List<String> allowedCommands;

    // Stats
    private final AtomicInteger totalDelivered = new AtomicInteger(0);
    private final AtomicInteger totalFailed    = new AtomicInteger(0);
    private final AtomicInteger totalPolls     = new AtomicInteger(0);
    private final AtomicLong    lastPollTime   = new AtomicLong(0);
    private volatile String     lastPollStatus = "ยังไม่ได้ poll";

    @Override
    public void onEnable() {
        saveDefaultConfig();
        loadConfiguration();

        if (!checkConfig()) {
            getServer().getPluginManager().disablePlugin(this);
            return;
        }

        startPollingTask();

        if (getCommand("mysqlcmd") != null) {
            getCommand("mysqlcmd").setExecutor(this);
        }

        printStartupBanner();
    }

    @Override
    public void onDisable() {
        if (pollingTask != null) pollingTask.cancel();
        int maxWait = 50;
        while (isPolling.get() && maxWait-- > 0) {
            try { Thread.sleep(100); } catch (InterruptedException ignored) {}
        }
        getLogger().info("════════════════════════════════════");
        getLogger().info("  MCStore Plugin ปิดการทำงานแล้ว");
        getLogger().info("  ส่งสำเร็จ : " + totalDelivered.get() + " คำสั่ง");
        getLogger().info("  ส่งล้มเหลว: " + totalFailed.get()    + " คำสั่ง");
        getLogger().info("════════════════════════════════════");
    }

    // ==========================================
    // Startup Banner
    // ==========================================

    private void printStartupBanner() {
        long intervalSec = pollingIntervalTicks / 20;
        getLogger().info("════════════════════════════════════════");
        getLogger().info("  MCStore Plugin v4.0  [HTTP API Mode]");
        getLogger().info("════════════════════════════════════════");
        getLogger().info("  Server ID   : " + serverId);
        getLogger().info("  API URL     : " + apiUrl);
        getLogger().info("  Poll ทุก    : " + intervalSec + " วินาที");
        getLogger().info("  สูงสุด/รอบ : " + maxCommandsPerCycle + " คำสั่ง");
        getLogger().info("  Whitelist   : " + allowedCommands.size() + " คำสั่ง → " + allowedCommands);
        getLogger().info("════════════════════════════════════════");
        getLogger().info("  ✔ พร้อมรับคำสั่งจาก MCStore แล้ว!");
        getLogger().info("════════════════════════════════════════");
    }

    // ==========================================
    // Config
    // ==========================================

    private void loadConfiguration() {
        apiUrl               = getConfig().getString("api.url", "").replaceAll("/$", "");
        serverId             = getConfig().getString("api.server_id", "");
        apiSecret            = getConfig().getString("api.secret", "");
        maxCommandsPerCycle  = getConfig().getInt("settings.max_commands_per_cycle", 20);
        pollingIntervalTicks = getConfig().getLong("settings.polling_interval_ticks", 600L);
        allowedCommands      = getConfig().getStringList("allowed_commands");
    }

    private boolean checkConfig() {
        if (apiUrl.isEmpty() || apiUrl.equalsIgnoreCase("https://yoursite.com")) {
            getLogger().severe("════ CONFIG ERROR ════════════════════");
            getLogger().severe("  กรุณาตั้งค่า api.url ใน config.yml!");
            getLogger().severe("═════════════════════════════════════");
            return false;
        }
        if (serverId.isEmpty()) {
            getLogger().severe("════ CONFIG ERROR ════════════════════");
            getLogger().severe("  กรุณาตั้งค่า api.server_id ใน config.yml!");
            getLogger().severe("═════════════════════════════════════");
            return false;
        }
        if (apiSecret.isEmpty() || apiSecret.equalsIgnoreCase("YOUR_PLUGIN_API_SECRET")) {
            getLogger().severe("════ CONFIG ERROR ════════════════════");
            getLogger().severe("  กรุณาตั้งค่า api.secret ใน config.yml!");
            getLogger().severe("═════════════════════════════════════");
            return false;
        }
        if (allowedCommands.isEmpty()) {
            getLogger().warning("⚠ allowed_commands ว่างเปล่า — ไม่มีคำสั่งที่รันได้!");
        }
        return true;
    }

    // ==========================================
    // Commands
    // ==========================================

    @Override
    public boolean onCommand(CommandSender sender, Command command, String label, String[] args) {
        if (!command.getName().equalsIgnoreCase("mysqlcmd")) return false;

        if (!sender.hasPermission("mysqlcmd.admin")) {
            sender.sendMessage("§cคุณไม่มีสิทธิ์ใช้คำสั่งนี้");
            return true;
        }

        String sub = args.length > 0 ? args[0].toLowerCase() : "";

        switch (sub) {
            case "reload" -> {
                reloadConfig();
                loadConfiguration();
                if (!checkConfig()) {
                    sender.sendMessage("§c[MCStore] Config error! ดู console");
                    return true;
                }
                if (pollingTask != null) pollingTask.cancel();
                startPollingTask();
                sender.sendMessage("§a[MCStore] Reload สำเร็จ!");
                printStartupBanner();
            }
            case "status" -> sendStatus(sender);
            case "poll" -> {
                sender.sendMessage("§e[MCStore] กำลัง poll ทันที...");
                Bukkit.getScheduler().runTaskAsynchronously(this, () -> {
                    if (!isPolling.compareAndSet(false, true)) {
                        sender.sendMessage("§c[MCStore] กำลัง poll อยู่แล้ว");
                        return;
                    }
                    try { pollAndExecute(); }
                    finally { isPolling.set(false); }
                    sender.sendMessage("§a[MCStore] Poll เสร็จแล้ว ดู console สำหรับผล");
                });
            }
            default -> {
                sender.sendMessage("§6[MCStore] §eคำสั่งที่ใช้ได้:");
                sender.sendMessage("§7  /mysqlcmd §freload §7— โหลด config ใหม่");
                sender.sendMessage("§7  /mysqlcmd §fstatus §7— แสดงสถานะปัจจุบัน");
                sender.sendMessage("§7  /mysqlcmd §fpoll   §7— poll คำสั่งทันที");
            }
        }
        return true;
    }

    private void sendStatus(CommandSender sender) {
        long intervalSec = pollingIntervalTicks / 20;
        String lastTime  = lastPollTime.get() == 0 ? "ยังไม่เคย" :
                DateTimeFormatter.ofPattern("HH:mm:ss").format(
                        LocalDateTime.now().minusSeconds(
                                (System.currentTimeMillis() - lastPollTime.get()) / 1000));

        sender.sendMessage("§6══════ MCStore Status ══════");
        sender.sendMessage("§7Server ID   : §f" + serverId);
        sender.sendMessage("§7API URL     : §f" + apiUrl);
        sender.sendMessage("§7Poll ทุก    : §f" + intervalSec + " วินาที");
        sender.sendMessage("§7Poll ล่าสุด : §f" + lastTime);
        sender.sendMessage("§7สถานะล่าสุด: §f" + lastPollStatus);
        sender.sendMessage("§7Polls รวม   : §f" + totalPolls.get() + " ครั้ง");
        sender.sendMessage("§7ส่งสำเร็จ  : §a" + totalDelivered.get() + " §7คำสั่ง");
        sender.sendMessage("§7ส่งล้มเหลว : §c" + totalFailed.get()    + " §7คำสั่ง");
        sender.sendMessage("§7Whitelist   : §f" + allowedCommands.size() + " คำสั่ง");
        sender.sendMessage("§7Online      : §f" + Bukkit.getOnlinePlayers().size() + " ผู้เล่น");
        sender.sendMessage("§6═══════════════════════════");
    }

    // ==========================================
    // Polling Task
    // ==========================================

    private void startPollingTask() {
        pollingTask = new BukkitRunnable() {
            @Override
            public void run() {
                if (!isPolling.compareAndSet(false, true)) return;
                try { pollAndExecute(); }
                finally { isPolling.set(false); }
            }
        }.runTaskTimerAsynchronously(this, pollingIntervalTicks, pollingIntervalTicks);
    }

    private void pollAndExecute() {
        lastPollTime.set(System.currentTimeMillis());
        totalPolls.incrementAndGet();

        try {
            long   timestamp = System.currentTimeMillis() / 1000;
            String signature = buildSignature(timestamp);

            URL url = new URL(apiUrl + "/api/plugin/pending");
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("GET");
            conn.setConnectTimeout(10000);
            conn.setReadTimeout(10000);
            conn.setRequestProperty("X-Server-ID",    serverId);
            conn.setRequestProperty("X-Timestamp",    String.valueOf(timestamp));
            conn.setRequestProperty("X-Signature",    signature);
            conn.setRequestProperty("X-Online-Count", String.valueOf(Bukkit.getOnlinePlayers().size()));

            int httpCode = conn.getResponseCode();
            if (httpCode != 200) {
                lastPollStatus = "HTTP " + httpCode;
                if (httpCode != 401) getLogger().warning("[MCStore] Poll HTTP " + httpCode);
                conn.disconnect();
                return;
            }

            String body = new String(conn.getInputStream().readAllBytes(), StandardCharsets.UTF_8);
            conn.disconnect();

            JsonObject json = JsonParser.parseString(body).getAsJsonObject();
            if (!json.get("success").getAsBoolean()) {
                lastPollStatus = "API returned success=false";
                return;
            }

            JsonArray deliveries = json.getAsJsonArray("deliveries");
            if (deliveries == null || deliveries.size() == 0) {
                lastPollStatus = "ไม่มีคำสั่งรอส่ง";
                return;
            }

            int count = Math.min(deliveries.size(), maxCommandsPerCycle);
            lastPollStatus = "พบ " + count + " คำสั่ง กำลังส่ง...";
            getLogger().info("[MCStore] พบ " + count + " คำสั่งที่รอส่ง");

            for (int i = 0; i < count; i++) {
                JsonObject d          = deliveries.get(i).getAsJsonObject();
                int        deliveryId = d.get("id").getAsInt();
                String     username   = d.get("username").getAsString();
                String     rawCommand = d.get("command").getAsString();

                String cmd = rawCommand
                        .replace("{player}", username)
                        .replace("{username}", username);

                if (!isCommandAllowed(cmd)) {
                    getLogger().warning("[MCStore] ⛔ บล็อก (ไม่อยู่ใน whitelist): " + cmd);
                    totalFailed.incrementAndGet();
                    sendCallbackAsync(deliveryId, "failed", "Command not in whitelist");
                    continue;
                }

                Bukkit.getScheduler().runTask(this, () -> {
                    try {
                        Bukkit.dispatchCommand(Bukkit.getConsoleSender(), cmd);
                        getLogger().info("[MCStore] ✔ ส่งสำเร็จ [#" + deliveryId + "] → " + cmd);
                        totalDelivered.incrementAndGet();
                        lastPollStatus = "ส่งสำเร็จล่าสุด: " + cmd;
                        sendCallbackAsync(deliveryId, "delivered", "OK");
                    } catch (Exception e) {
                        getLogger().severe("[MCStore] ✘ คำสั่งล้มเหลว [#" + deliveryId + "]: " + e.getMessage());
                        totalFailed.incrementAndGet();
                        sendCallbackAsync(deliveryId, "failed", "Exception: " + e.getMessage());
                    }
                });
            }

        } catch (Exception e) {
            lastPollStatus = "Error: " + e.getMessage();
            getLogger().warning("[MCStore] Poll error: " + e.getMessage());
        }
    }

    // ==========================================
    // Callback
    // ==========================================

    private void sendCallbackAsync(int deliveryId, String status, String response) {
        Bukkit.getScheduler().runTaskAsynchronously(this, () -> {
            try {
                long   timestamp = System.currentTimeMillis() / 1000;
                String signature = buildSignature(timestamp);

                JsonObject bodyJson = new JsonObject();
                bodyJson.addProperty("delivery_id", deliveryId);
                bodyJson.addProperty("status",      status);
                bodyJson.addProperty("response",    response);
                byte[] bodyBytes = bodyJson.toString().getBytes(StandardCharsets.UTF_8);

                URL url = new URL(apiUrl + "/api/plugin/callback");
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setConnectTimeout(10000);
                conn.setReadTimeout(10000);
                conn.setDoOutput(true);
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setRequestProperty("X-Server-ID",  serverId);
                conn.setRequestProperty("X-Timestamp",  String.valueOf(timestamp));
                conn.setRequestProperty("X-Signature",  signature);

                try (OutputStream os = conn.getOutputStream()) { os.write(bodyBytes); }

                int code = conn.getResponseCode();
                if (code != 200) {
                    getLogger().warning("[MCStore] Callback HTTP " + code + " [#" + deliveryId + "]");
                }
                conn.disconnect();

            } catch (Exception e) {
                getLogger().warning("[MCStore] Callback error [#" + deliveryId + "]: " + e.getMessage());
            }
        });
    }

    // ==========================================
    // Helpers
    // ==========================================

    private boolean isCommandAllowed(String command) {
        if (allowedCommands == null || allowedCommands.isEmpty()) return false;

        String cmd = command.trim().toLowerCase();
        if (cmd.startsWith("/")) cmd = cmd.substring(1);
        if (cmd.startsWith("execute") || cmd.contains(" run ")) return false;

        String base = cmd.split("\\s+")[0];
        if (base.contains(":")) base = base.substring(base.indexOf(':') + 1);

        return allowedCommands.contains(base);
    }

    private String buildSignature(long timestamp) {
        try {
            String payload = serverId + ":" + timestamp;
            Mac mac = Mac.getInstance("HmacSHA256");
            mac.init(new SecretKeySpec(apiSecret.getBytes(StandardCharsets.UTF_8), "HmacSHA256"));
            byte[] hash = mac.doFinal(payload.getBytes(StandardCharsets.UTF_8));
            StringBuilder sb = new StringBuilder(hash.length * 2);
            for (byte b : hash) sb.append(String.format("%02x", b));
            return sb.toString();
        } catch (Exception e) {
            getLogger().severe("[MCStore] HMAC error: " + e.getMessage());
            return "";
        }
    }
}
