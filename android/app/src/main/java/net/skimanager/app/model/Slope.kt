package net.skimanager.app.model

import com.google.gson.annotations.SerializedName

enum class SlopeDifficulty(
    val label: String,
    val shortLabel: String,
    val colorHex: String,
    val baseCutCost: Long
) {
    @SerializedName("green")
    GREEN("Green Circle (Beginner)", "Green", "#16A34A", 45000),

    @SerializedName("blue")
    BLUE("Blue Square (Intermediate)", "Blue", "#2563EB", 75000),

    @SerializedName("black")
    BLACK("Black Diamond (Advanced)", "Black", "#1E293B", 120000),

    @SerializedName("double_black")
    DOUBLE_BLACK("Double Black (Expert)", "Double Black", "#0F172A", 185000),

    @SerializedName("terrain_park")
    TERRAIN_PARK("Terrain Park (Freestyle)", "Terrain Park", "#9333EA", 140000);

    companion object {
        fun fromCode(code: String): SlopeDifficulty = when (code.lowercase()) {
            "green" -> GREEN
            "blue" -> BLUE
            "black" -> BLACK
            "double_black" -> DOUBLE_BLACK
            "terrain_park" -> TERRAIN_PARK
            else -> BLUE
        }
    }
}

enum class SnowQuality(val label: String) {
    @SerializedName("powder")
    DEEP_POWDER("Deep Powder"),

    @SerializedName("packed_powder")
    PACKED_POWDER("Packed Powder"),

    @SerializedName("corduroy")
    GROOMED_CORDUROY("Groomed Corduroy"),

    @SerializedName("variable")
    VARIABLE("Variable Snow"),

    @SerializedName("slush")
    SPRING_SLUSH("Spring Slush")
}

data class Slope(
    val id: String = java.util.UUID.randomUUID().toString(),
    val name: String,
    val difficulty: SlopeDifficulty,
    val sectorNumber: Int = 1,
    val lengthMeters: Int,
    val verticalDropMeters: Int,
    var groomingPercent: Int = 90,
    var snowmakingEquipped: Boolean = true,
    var nightSkiingLighted: Boolean = false,
    var isOpen: Boolean = true,
    var snowDepthCm: Int = 140,
    var snowQuality: SnowQuality = SnowQuality.GROOMED_CORDUROY
) {
    val dailyUpkeepCost: Int get() = 85 + (if (snowmakingEquipped) 30 else 0) + (if (nightSkiingLighted) 25 else 0)
}
