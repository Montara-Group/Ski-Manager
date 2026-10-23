package net.skimanager.app.network

import com.google.gson.Gson
import com.google.gson.JsonObject
import com.google.gson.reflect.TypeToken
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import net.skimanager.app.model.LeaderboardEntry
import net.skimanager.app.model.RealMapSegment
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import java.util.concurrent.TimeUnit

data class ServerHealth(
    val isHealthy: Boolean,
    val message: String
)

data class ClientSyncPayload(
    val cash: Long,
    val game_day: Int,
    val daily_visitors: Int,
    val ticket_price_adult: Int,
    val ticket_price_child: Int,
    val built_segments_count: Int,
    val outstanding_loan: Long
)

data class ApiFinances(
    val cash: Long?,
    val difficulty: String?,
    val daily_visitors: Int?
)

data class ApiWeather(
    val game_day: Int?,
    val temperature: Int?,
    val snowfall: Int?,
    val base_depth: Int?,
    val wind_speed: Int?,
    val condition: String?
)

data class ApiResortDataResponse(
    val status: String?,
    val server_time: Long?,
    val resort_name: String?,
    val game_day: Int?,
    val finances: ApiFinances?,
    val weather: ApiWeather?,
    val segments: List<RealMapSegment>?
)

class SkiManagerApiClient(
    private val baseUrl: String = "https://ski-manager.net"
) {
    private val client = OkHttpClient.Builder()
        .connectTimeout(8, TimeUnit.SECONDS)
        .readTimeout(10, TimeUnit.SECONDS)
        .writeTimeout(8, TimeUnit.SECONDS)
        .build()

    private val gson = Gson()
    private val jsonMediaType = "application/json; charset=utf-8".toMediaType()

    suspend fun checkServerHealth(): ServerHealth = withContext(Dispatchers.IO) {
        try {
            val request = Request.Builder()
                .url("$baseUrl/api/resort-data")
                .header("User-Agent", "SkiManager-Android/1.0")
                .build()

            client.newCall(request).execute().use { response ->
                if (response.isSuccessful) {
                    ServerHealth(true, "Online · Connected to ski-manager.net")
                } else {
                    ServerHealth(false, "Server Error: HTTP ${response.code}")
                }
            }
        } catch (e: Exception) {
            ServerHealth(false, "Operating Offline (Cached Data)")
        }
    }

    suspend fun fetchResortData(): ApiResortDataResponse? = withContext(Dispatchers.IO) {
        try {
            val request = Request.Builder()
                .url("$baseUrl/api/resort-data")
                .header("User-Agent", "SkiManager-Android/1.0")
                .build()

            client.newCall(request).execute().use { response ->
                if (!response.isSuccessful) return@withContext null
                val body = response.body?.string() ?: return@withContext null
                gson.fromJson(body, ApiResortDataResponse::class.java)
            }
        } catch (e: Exception) {
            null
        }
    }

    suspend fun fetchLeaderboard(): List<LeaderboardEntry> = withContext(Dispatchers.IO) {
        try {
            val request = Request.Builder()
                .url("$baseUrl/api/leaderboard")
                .header("User-Agent", "SkiManager-Android/1.0")
                .build()

            client.newCall(request).execute().use { response ->
                if (!response.isSuccessful) return@withContext emptyList()
                val body = response.body?.string() ?: return@withContext emptyList()
                val listType = object : TypeToken<List<LeaderboardEntry>>() {}.type
                gson.fromJson<List<LeaderboardEntry>>(body, listType) ?: emptyList()
            }
        } catch (e: Exception) {
            emptyList()
        }
    }

    suspend fun pushSync(payload: ClientSyncPayload): Boolean = withContext(Dispatchers.IO) {
        try {
            val json = gson.toJson(payload)
            val requestBody = json.toRequestBody(jsonMediaType)
            val request = Request.Builder()
                .url("$baseUrl/api/sync")
                .header("User-Agent", "SkiManager-Android/1.0")
                .post(requestBody)
                .build()

            client.newCall(request).execute().use { response ->
                response.isSuccessful
            }
        } catch (e: Exception) {
            false
        }
    }
}
