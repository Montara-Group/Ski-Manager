import Foundation
import SwiftUI

public enum WeatherCondition: String, Codable, CaseIterable {
    case bluebirdPowder = "Bluebird Powder"
    case lightSnow = "Gentle Snowfall"
    case heavyBlizzard = "Mountain Blizzard"
    case sunny = "Sunny & Clear"
    case partlyCloudy = "Partly Cloudy"
    case denseFog = "High Alpine Fog"
    case springSlush = "Spring Thaw"
    
    public var sfSymbol: String {
        switch self {
        case .bluebirdPowder: return "sun.snow.fill"
        case .lightSnow: return "cloud.snow.fill"
        case .heavyBlizzard: return "wind.snow"
        case .sunny: return "sun.max.fill"
        case .partlyCloudy: return "cloud.sun.fill"
        case .denseFog: return "cloud.fog.fill"
        case .springSlush: return "cloud.drizzle.fill"
        }
    }
    
    public var symbolColor: Color {
        switch self {
        case .bluebirdPowder, .sunny: return AlpineTheme.peakAmber
        case .lightSnow: return AlpineTheme.glacierBlue
        case .heavyBlizzard, .denseFog: return .cyan
        case .partlyCloudy: return .yellow
        case .springSlush: return .mint
        }
    }
}

public struct DailyForecast: Identifiable, Codable, Equatable, Hashable {
    public var id: UUID
    public var dayOffset: Int
    public var dayName: String
    public var condition: WeatherCondition
    public var highTempC: Double
    public var lowTempC: Double
    public var expectedSnowCm: Int
    
    public init(
        id: UUID = UUID(),
        dayOffset: Int,
        dayName: String,
        condition: WeatherCondition,
        highTempC: Double,
        lowTempC: Double,
        expectedSnowCm: Int
    ) {
        self.id = id
        self.dayOffset = dayOffset
        self.dayName = dayName
        self.condition = condition
        self.highTempC = highTempC
        self.lowTempC = lowTempC
        self.expectedSnowCm = expectedSnowCm
    }
}

public struct MountainWeather: Codable, Equatable, Hashable {
    public var condition: WeatherCondition
    public var temperatureCelsius: Double
    public var newSnowfall24hCm: Int
    public var baseDepthCm: Int
    public var windSpeedKmh: Int
    public var visibilityKm: Double
    public var avalancheDangerLevel: Int // 1 to 5
    public var isSnowmakingOptimal: Bool
    public var forecast7Day: [DailyForecast]
    
    public init(
        condition: WeatherCondition = .bluebirdPowder,
        temperatureCelsius: Double = -4.5,
        newSnowfall24hCm: Int = 18,
        baseDepthCm: Int = 142,
        windSpeedKmh: Int = 14,
        visibilityKm: Double = 12.0,
        avalancheDangerLevel: Int = 2,
        isSnowmakingOptimal: Bool = true,
        forecast7Day: [DailyForecast] = []
    ) {
        self.condition = condition
        self.temperatureCelsius = temperatureCelsius
        self.newSnowfall24hCm = newSnowfall24hCm
        self.baseDepthCm = baseDepthCm
        self.windSpeedKmh = windSpeedKmh
        self.visibilityKm = visibilityKm
        self.avalancheDangerLevel = avalancheDangerLevel
        self.isSnowmakingOptimal = isSnowmakingOptimal
        self.forecast7Day = forecast7Day.isEmpty ? Self.generateDefaultForecast() : forecast7Day
    }
    
    public var temperatureFahrenheit: Double {
        (temperatureCelsius * 9.0 / 5.0) + 32.0
    }
    
    public var formattedTempC: String {
        String(format: "%.0f°C", temperatureCelsius)
    }
    
    public var formattedTempF: String {
        String(format: "%.0f°F", temperatureFahrenheit)
    }
    
    public var avalancheRiskTitle: String {
        switch avalancheDangerLevel {
        case 1: return "Low Risk"
        case 2: return "Moderate"
        case 3: return "Considerable"
        case 4: return "High Alert"
        case 5: return "Extreme Risk"
        default: return "Normal"
        }
    }
    
    public var avalancheRiskColor: Color {
        switch avalancheDangerLevel {
        case 1: return AlpineTheme.forestGreen
        case 2: return AlpineTheme.peakAmber
        case 3: return .orange
        case 4, 5: return AlpineTheme.avalancheRed
        default: return .secondary
        }
    }
    
    public static func generateDefaultForecast() -> [DailyForecast] {
        let conditions: [WeatherCondition] = [
            .bluebirdPowder, .lightSnow, .sunny, .partlyCloudy, .heavyBlizzard, .lightSnow, .sunny
        ]
        let days = ["Tomorrow", "Wed", "Thu", "Fri", "Sat", "Sun", "Mon"]
        var results: [DailyForecast] = []
        for i in 0..<7 {
            results.append(
                DailyForecast(
                    dayOffset: i + 1,
                    dayName: days[i],
                    condition: conditions[i],
                    highTempC: Double.random(in: -3.0...1.0),
                    lowTempC: Double.random(in: -10.0 ... -4.0),
                    expectedSnowCm: [12, 6, 0, 0, 24, 8, 2][i]
                )
            )
        }
        return results
    }
}
