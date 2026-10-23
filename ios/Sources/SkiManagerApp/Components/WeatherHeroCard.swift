import SwiftUI

public struct WeatherHeroCard: View {
    public let weather: MountainWeather
    public let resortName: String
    
    @State private var isForecastExpanded: Bool = false
    
    public init(weather: MountainWeather, resortName: String) {
        self.weather = weather
        self.resortName = resortName
    }
    
    public var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            // Header Location & Condition
            HStack(alignment: .top) {
                VStack(alignment: .leading, spacing: 4) {
                    Text(resortName)
                        .font(.headline)
                        .foregroundStyle(.secondary)
                    
                    Text(weather.condition.rawValue)
                        .font(.title2.weight(.bold))
                        .foregroundStyle(.primary)
                }
                
                Spacer()
                
                Image(systemName: weather.condition.sfSymbol)
                    .symbolRenderingMode(.hierarchical)
                    .foregroundStyle(weather.condition.symbolColor)
                    .font(.system(size: 42))
                    .shadow(color: weather.condition.symbolColor.opacity(0.3), radius: 8, x: 0, y: 4)
            }
            
            // Primary Telemetry Cluster
            HStack(spacing: 24) {
                // Big Temperature Readout
                VStack(alignment: .leading, spacing: 2) {
                    Text(weather.formattedTempC)
                        .font(.system(size: 48, weight: .black, design: .rounded))
                        .monospacedDigit()
                        .foregroundStyle(.primary)
                    
                    Text(weather.formattedTempF)
                        .font(.caption.weight(.medium))
                        .foregroundStyle(.secondary)
                }
                
                Divider()
                    .frame(height: 50)
                
                // Key Mountain Metrics
                VStack(alignment: .leading, spacing: 6) {
                    HStack(spacing: 6) {
                        Image(systemName: "snowflake")
                            .foregroundStyle(AlpineTheme.glacierBlue)
                            .font(.caption)
                        Text("24h Snow:")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                        Text("+\(weather.newSnowfall24hCm) cm")
                            .font(.caption.weight(.bold))
                            .monospacedDigit()
                    }
                    
                    HStack(spacing: 6) {
                        Image(systemName: "ruler.fill")
                            .foregroundStyle(.cyan)
                            .font(.caption)
                        Text("Base Depth:")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                        Text("\(weather.baseDepthCm) cm")
                            .font(.caption.weight(.bold))
                            .monospacedDigit()
                    }
                    
                    HStack(spacing: 6) {
                        Image(systemName: "wind")
                            .foregroundStyle(.teal)
                            .font(.caption)
                        Text("Wind:")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                        Text("\(weather.windSpeedKmh) km/h")
                            .font(.caption.weight(.bold))
                            .monospacedDigit()
                    }
                }
                
                Spacer()
            }
            
            // Avalanche & Safety Badge
            HStack {
                HStack(spacing: 6) {
                    Circle()
                        .fill(weather.avalancheRiskColor)
                        .frame(width: 8, height: 8)
                    Text("Avalanche Danger: \(weather.avalancheRiskTitle) (Level \(weather.avalancheDangerLevel)/5)")
                        .font(.caption.weight(.semibold))
                        .foregroundStyle(.primary)
                }
                .padding(.horizontal, 10)
                .padding(.vertical, 6)
                .background {
                    Capsule()
                        .fill(weather.avalancheRiskColor.opacity(0.15))
                        .overlay(Capsule().strokeBorder(weather.avalancheRiskColor.opacity(0.3), lineWidth: 0.5))
                }
                
                Spacer()
                
                Button {
                    withAnimation(AlpineTheme.snappySpring) {
                        isForecastExpanded.toggle()
                    }
                } label: {
                    HStack(spacing: 4) {
                        Text(isForecastExpanded ? "Hide Forecast" : "7-Day Outlook")
                            .font(.caption.weight(.semibold))
                        Image(systemName: isForecastExpanded ? "chevron.up" : "chevron.down")
                            .font(.caption2)
                    }
                    .foregroundStyle(AlpineTheme.glacierBlue)
                }
            }
            
            // Expandable 7-Day Outlook Strip
            if isForecastExpanded {
                ScrollView(.horizontal, showsIndicators: false) {
                    HStack(spacing: 12) {
                        ForEach(weather.forecast7Day) { forecast in
                            VStack(spacing: 6) {
                                Text(forecast.dayName)
                                    .font(.caption2.weight(.semibold))
                                    .foregroundStyle(.secondary)
                                
                                Image(systemName: forecast.condition.sfSymbol)
                                    .symbolRenderingMode(.hierarchical)
                                    .foregroundStyle(forecast.condition.symbolColor)
                                    .font(.title3)
                                
                                Text(String(format: "%.0f°", forecast.highTempC))
                                    .font(.caption.weight(.bold))
                                    .monospacedDigit()
                                
                                if forecast.expectedSnowCm > 0 {
                                    Text("+\(forecast.expectedSnowCm)cm")
                                        .font(.system(size: 10, weight: .bold))
                                        .foregroundStyle(AlpineTheme.glacierBlue)
                                        .monospacedDigit()
                                } else {
                                    Text("-")
                                        .font(.system(size: 10))
                                        .foregroundStyle(.tertiary)
                                }
                            }
                            .padding(.vertical, 10)
                            .padding(.horizontal, 12)
                            .background {
                                RoundedRectangle(cornerRadius: 12, style: .continuous)
                                    .fill(Color(uiColor: .secondarySystemGroupedBackground).opacity(0.6))
                            }
                        }
                    }
                    .padding(.top, 4)
                }
                .transition(.opacity.combined(with: .move(edge: .top)))
            }
        }
        .alpineGlassCard()
    }
}
