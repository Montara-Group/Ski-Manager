package net.skimanager.app.model

import com.google.gson.annotations.SerializedName

data class RealMapSegment(
    val id: Int,
    @SerializedName("resort_map") val resortMap: String = "ParkCity",
    val type: String, // "downhill" or "lift"
    var difficulty: String = "blue",
    var name: String,
    val points: List<List<Double>>, // Leaflet coordinates [[lat/y, lng/x], ...]
    @SerializedName("length_meters") var lengthMeters: Int = 1200,
    val sector: Int = 1,
    @SerializedName("active") var active: Boolean = true,
    var isBuilt: Boolean = true,
    var isOpen: Boolean = true,
    var conditionPercent: Int = 95,
    var groomingPercent: Int = 85
) {
    val isLift: Boolean get() = type == "lift"
    val isSlope: Boolean get() = type == "downhill"

    val difficultyEnum: SlopeDifficulty
        get() = SlopeDifficulty.fromCode(difficulty)

    val capacity: Int
        get() = if (isLift) 1800 else 0

    val constructionCost: Long
        get() = if (isLift) (lengthMeters * 180L).coerceAtLeast(150000L) else (lengthMeters * 45L).coerceAtLeast(40000L)
}

data class RealMapSector(
    val id: Int,
    @SerializedName("resort_map") val resortMap: String = "ParkCity",
    val name: String,
    val color: String = "#3b82f6",
    @SerializedName("boundary_points") val boundaryPoints: List<List<Double>> = emptyList(),
    val released: Boolean = true,
    val visible: Boolean = true
)

data class LeaderboardEntry(
    val id: Long,
    val username: String,
    val resortName: String,
    val cash: Long,
    val reputation: Int,
    val rankPosition: Int
)
