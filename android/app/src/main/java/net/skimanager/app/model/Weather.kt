package net.skimanager.app.model

import com.google.gson.annotations.SerializedName

enum class WeatherCondition(val label: String, val iconEmoji: String) {
    @SerializedName("bluebirdPowder")
    BLUEBIRD_POWDER("Bluebird Powder", "❄️"),

    @SerializedName("lightSnow")
    LIGHT_SNOW("Light Snow", "🌨️"),

    @SerializedName("heavyBlizzard")
    HEAVY_BLIZZARD("Heavy Blizzard", "💨"),

    @SerializedName("sunny")
    SUNNY("Crisp Sunshine", "☀️"),

    @SerializedName("partlyCloudy")
    PARTLY_CLOUDY("Partly Cloudy", "⛅"),

    @SerializedName("denseFog")
    DENSE_FOG("Mountain Fog", "🌫️"),

    @SerializedName("springSlush")
    SPRING_SLUSH("Spring Slush", "🌤️")
}

data class MountainWeather(
    var condition: WeatherCondition = WeatherCondition.BLUEBIRD_POWDER,
    var temperatureCelsius: Double = -4.0,
    var newSnowfall24hCm: Int = 15,
    var baseDepthCm: Int = 142,
    var windSpeedKmh: Int = 14,
    var visibilityKm: Double = 15.0,
    var avalancheDangerLevel: Int = 2, // 1 (Low) to 5 (Extreme)
    var isSnowmakingOptimal: Boolean = true
) {
    val avalancheWarningLabel: String
        get() = when (avalancheDangerLevel) {
            1 -> "Low (Level 1)"
            2 -> "Moderate (Level 2)"
            3 -> "Considerable (Level 3)"
            4 -> "High (Level 4)"
            else -> "Extreme (Level 5)"
        }
}
