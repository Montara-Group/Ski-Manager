import Foundation
import SwiftUI

/// Authentic ski-manager.net website snapshot stats bar
/// Displays Treasury Balance, Daily Attendance, Net Profit, Mountain Weather, and Génépis
public struct ResortStatsBarView: View {
    @Environment(ResortStore.self) private var store
    
    public init() {}
    
    public var body: some View {
        HStack(spacing: 8) {
            // 1. Balance
            StatsBarCell(
                title: "Balance",
                value: "€\(store.resort.cash.formatted())",
                tintColor: store.resort.cash >= 0 ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed
            )
            
            // 2. Visitors
            StatsBarCell(
                title: "Visitors",
                value: "\(store.resort.totalVisitorsToday.formatted())",
                tintColor: .primary
            )
            
            // 3. Profit
            StatsBarCell(
                title: "Profit",
                value: (store.resort.netDailyProfit >= 0 ? "+" : "") + "€\(store.resort.netDailyProfit.formatted())",
                tintColor: store.resort.netDailyProfit >= 0 ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed
            )
            
            // 4. Weather
            StatsBarCell(
                title: "Weather",
                value: "\(Int(round(store.resort.weather.temperatureCelsius)))°C",
                tintColor: AlpineTheme.glacierBlue,
                icon: store.resort.weather.condition.sfSymbol
            )
            
            // 5. Génépis
            StatsBarCell(
                title: "Génépis",
                value: "\(store.resort.genepis) 🌿",
                tintColor: AlpineTheme.peakAmber
            )
        }
        .padding(.horizontal, 10)
        .padding(.vertical, 8)
        .background {
            RoundedRectangle(cornerRadius: 16, style: .continuous)
                .fill(.ultraThinMaterial)
                .shadow(color: .black.opacity(0.12), radius: 8, y: 3)
        }
        .overlay {
            RoundedRectangle(cornerRadius: 16, style: .continuous)
                .stroke(Color.white.opacity(0.15), lineWidth: 1)
        }
    }
}

public struct StatsBarCell: View {
    public let title: String
    public let value: String
    public let tintColor: Color
    public var icon: String? = nil
    
    public var body: some View {
        VStack(spacing: 2) {
            Text(title.uppercased())
                .font(.system(size: 9, weight: .bold, design: .rounded))
                .foregroundStyle(.secondary)
            
            HStack(spacing: 2) {
                if let ic = icon {
                    Image(systemName: ic)
                        .font(.system(size: 9, weight: .bold))
                        .foregroundStyle(tintColor)
                }
                Text(value)
                    .font(.system(size: 11, weight: .bold, design: .rounded))
                    .foregroundStyle(tintColor)
                    .lineLimit(1)
                    .minimumScaleFactor(0.75)
                    .monospacedDigit()
            }
        }
        .frame(maxWidth: .infinity)
        .padding(.vertical, 3)
        .background {
            RoundedRectangle(cornerRadius: 8, style: .continuous)
                .fill(Color.primary.opacity(0.04))
        }
    }
}
