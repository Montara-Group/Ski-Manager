import SwiftUI

public struct ResortOpsView: View {
    @Environment(ResortStore.self) private var store
    
    public init() {}
    
    public var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 20) {
                    // Live Weather Card
                    WeatherHeroCard(
                        weather: store.resort.weather,
                        resortName: store.resort.name
                    )
                    .padding(.horizontal, 16)
                    
                    // Core Operational Telemetry Grid
                    LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 12) {
                        StatPillView(
                            title: "Daily Visitors",
                            value: store.resort.totalVisitorsToday.formatted(),
                            sfSymbol: "person.2.fill",
                            tintColor: AlpineTheme.glacierBlue,
                            subtitle: "\(store.resort.reputationScore)% satisfaction"
                        )
                        
                        StatPillView(
                            title: "Mountain Safety",
                            value: "\(store.resort.mountainSafetyScore)/100",
                            sfSymbol: "shield.checkered",
                            tintColor: store.resort.mountainSafetyScore > 70 ? AlpineTheme.forestGreen : AlpineTheme.peakAmber,
                            subtitle: "\(store.resort.staff.filter { $0.role == .skiPatrol }.count) Patrollers"
                        )
                        
                        StatPillView(
                            title: "Skiable Terrain",
                            value: String(format: "%.1f km", store.resort.totalSkiableLengthKm),
                            sfSymbol: "mountain.2.fill",
                            tintColor: .indigo,
                            subtitle: "\(store.resort.openSlopesCount) Trails Open"
                        )
                        
                        StatPillView(
                            title: "Hourly Lift Power",
                            value: "\(store.resort.totalMountainCapacityPerHour.formatted())/h",
                            sfSymbol: "chairlift.fill",
                            tintColor: .teal,
                            subtitle: "\(store.resort.runningLiftsCount) Active Lifts"
                        )
                    }
                    .padding(.horizontal, 16)
                    
                    // Mountain Operations Systems
                    VStack(alignment: .leading, spacing: 12) {
                        Text("Operational Facilities")
                            .font(.headline)
                            .foregroundStyle(.primary)
                            .padding(.horizontal, 16)
                        
                        VStack(spacing: 12) {
                            // 1. Grooming Fleet Status
                            OpsSystemRow(
                                title: "Snowcat Grooming Fleet",
                                subtitle: "Corduroy trail surface maintenance",
                                icon: "steeringwheel",
                                iconColor: AlpineTheme.glacierBlue,
                                statusBadge: "\(store.resort.staff.filter { $0.role == .groomer }.count) Groomers Active",
                                detailText: "Regular grooming ensures packed powder and prevents mogul ruts."
                            )
                            
                            // 2. Snowmaking Plant
                            OpsSystemRow(
                                title: "Automated Snowmaking",
                                subtitle: store.resort.weather.isSnowmakingOptimal ? "Optimal Wet-Bulb (-4°C)" : "Sub-Optimal Temperature",
                                icon: "fan.fill",
                                iconColor: store.resort.weather.isSnowmakingOptimal ? .cyan : .secondary,
                                statusBadge: store.resort.weather.isSnowmakingOptimal ? "READY" : "STANDBY",
                                detailText: "Water reservoir at 88% capacity. High-pressure lances ready."
                            )
                            
                            // 3. Avalanche Control & Ski Patrol
                            OpsSystemRow(
                                title: "Ski Patrol & Avalanche Sweep",
                                subtitle: "Risk Level: \(store.resort.weather.avalancheRiskTitle)",
                                icon: "cross.case.fill",
                                iconColor: store.resort.weather.avalancheRiskColor,
                                statusBadge: "\(store.resort.mountainSafetyScore)% Coverage",
                                detailText: "Sweep teams perform early morning blast drops on ridge chutes."
                            )
                        }
                        .padding(.horizontal, 16)
                    }
                    
                    // Quick Action Dock
                    VStack(alignment: .leading, spacing: 12) {
                        Text("Immediate Actions")
                            .font(.headline)
                            .foregroundStyle(.primary)
                            .padding(.horizontal, 16)
                        
                        QuickActionGrid()
                            .padding(.horizontal, 16)
                    }
                }
                .padding(.vertical, 16)
            }
            .navigationTitle("Mountain Ops")
            .navigationBarTitleDisplayMode(.large)
        }
    }
}

// MARK: - Operations System Row

public struct OpsSystemRow: View {
    public let title: String
    public let subtitle: String
    public let icon: String
    public let iconColor: Color
    public let statusBadge: String
    public let detailText: String
    
    public var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack(spacing: 12) {
                ZStack {
                    RoundedRectangle(cornerRadius: 12, style: .continuous)
                        .fill(iconColor.opacity(0.15))
                        .frame(width: 40, height: 40)
                    
                    Image(systemName: icon)
                        .font(.title3)
                        .foregroundStyle(iconColor)
                }
                
                VStack(alignment: .leading, spacing: 2) {
                    Text(title)
                        .font(.headline)
                        .foregroundStyle(.primary)
                    
                    Text(subtitle)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                
                Spacer()
                
                Text(statusBadge)
                    .font(.system(size: 11, weight: .bold))
                    .foregroundStyle(iconColor)
                    .padding(.horizontal, 8)
                    .padding(.vertical, 4)
                    .background(Capsule().fill(iconColor.opacity(0.15)))
            }
            
            Text(detailText)
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .alpineGlassCard()
    }
}
