import Foundation
import SwiftUI

public struct DashboardView: View {
    @Environment(ResortStore.self) private var store
    @Binding var selectedTab: Int
    
    @State private var showingAdvanceDayConfirm: Bool = false
    @State private var bonusClaimSuccess: Bool = false
    @State private var bonusClaimText: String = ""
    
    public init(selectedTab: Binding<Int> = .constant(0)) {
        self._selectedTab = selectedTab
    }
    
    private var greetingTimeText: String {
        let hour = Calendar.current.component(.hour, from: Date())
        if hour < 12 { return "Good morning" }
        if hour < 17 { return "Good afternoon" }
        return "Good evening"
    }
    
    public var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 16) {
                    // MARK: - 1. Authentic Website Header
                    VStack(alignment: .leading, spacing: 4) {
                        HStack {
                            Text("\(greetingTimeText), \(store.resort.directorUsername)")
                                .font(.title2.weight(.bold))
                                .foregroundStyle(.primary)
                            
                            Spacer()
                            
                            // Edit / Settings indicator
                            Image(systemName: "person.crop.circle.fill")
                                .font(.title2)
                                .foregroundStyle(AlpineTheme.glacierBlue)
                        }
                        
                        Text("Day \(store.resort.gameDay) · Season \(store.resort.seasonNumber): \(store.resort.mapName) · \(store.resort.netDailyProfit >= 0 ? "Profitable today" : "Operating at deficit")")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                    .padding(.horizontal, 16)
                    .padding(.top, 4)
                    
                    // MARK: - 2. Signature Website Top Stats Bar
                    ResortStatsBarView()
                        .padding(.horizontal, 16)
                    
                    // MARK: - 3. Season Progress Status Bar
                    VStack(alignment: .leading, spacing: 6) {
                        HStack {
                            Text("Season 1: Park City")
                                .font(.caption2.weight(.bold))
                                .foregroundStyle(.secondary)
                            Spacer()
                            Text("Day \(store.resort.gameDay) / \(store.resort.totalSeasonDays)")
                                .font(.caption2.weight(.semibold))
                                .foregroundStyle(.primary)
                                .monospacedDigit()
                        }
                        
                        GeometryReader { g in
                            ZStack(alignment: .leading) {
                                Capsule()
                                    .fill(Color.primary.opacity(0.1))
                                Capsule()
                                    .fill(
                                        LinearGradient(
                                            colors: [AlpineTheme.glacierBlue, AlpineTheme.peakAmber],
                                            startPoint: .leading,
                                            endPoint: .trailing
                                        )
                                    )
                                    .frame(width: max(10, g.size.width * store.resort.seasonProgressRatio))
                            }
                        }
                        .frame(height: 6)
                    }
                    .padding(.horizontal, 16)
                    
                    // MARK: - 4. Daily Bonus Claim Banner
                    if store.resort.canClaimDailyBonus {
                        Button {
                            if let reward = store.claimDailyBonus() {
                                bonusClaimText = "+€\(reward.cash.formatted()) · +\(reward.genepis) 🌿 Génépis"
                                withAnimation(AlpineTheme.bouncySpring) {
                                    bonusClaimSuccess = true
                                }
                            }
                        } label: {
                            HStack(spacing: 12) {
                                ZStack {
                                    Circle()
                                        .fill(AlpineTheme.peakAmber.opacity(0.2))
                                        .frame(width: 40, height: 40)
                                    Image(systemName: "flame.fill")
                                        .foregroundStyle(AlpineTheme.peakAmber)
                                        .font(.title3)
                                }
                                
                                VStack(alignment: .leading, spacing: 2) {
                                    Text("Claim Day \(store.resort.gameDay) Bonus Streak!")
                                        .font(.subheadline.weight(.bold))
                                        .foregroundStyle(.primary)
                                    Text("Streak #\(store.resort.dailyBonusStreak) · Claim cash & Génépis")
                                        .font(.caption2)
                                        .foregroundStyle(.secondary)
                                }
                                
                                Spacer()
                                
                                Text("Claim")
                                    .font(.caption.weight(.bold))
                                    .padding(.horizontal, 14)
                                    .padding(.vertical, 6)
                                    .background {
                                        Capsule().fill(AlpineTheme.peakAmber)
                                    }
                                    .foregroundStyle(.black)
                            }
                            .padding(12)
                            .background {
                                RoundedRectangle(cornerRadius: 16, style: .continuous)
                                    .fill(AlpineTheme.peakAmber.opacity(0.12))
                            }
                            .overlay {
                                RoundedRectangle(cornerRadius: 16, style: .continuous)
                                    .stroke(AlpineTheme.peakAmber.opacity(0.4), lineWidth: 1)
                            }
                        }
                        .padding(.horizontal, 16)
                        .sensoryFeedback(.success, trigger: bonusClaimSuccess)
                    }
                    
                    // MARK: - 5. Primary Advance Day Bar
                    VStack(spacing: 10) {
                        Button {
                            withAnimation(AlpineTheme.snappySpring) {
                                store.advanceDay()
                            }
                        } label: {
                            HStack(spacing: 10) {
                                Image(systemName: "forward.fill")
                                    .font(.headline)
                                Text("Advance Game Day \(store.resort.gameDay + 1)")
                                    .font(.headline.weight(.bold))
                                Spacer()
                                Text("Tick Day")
                                    .font(.caption.weight(.semibold))
                                    .padding(.horizontal, 10)
                                    .padding(.vertical, 4)
                                    .background {
                                        Capsule().fill(Color.black.opacity(0.2))
                                    }
                            }
                            .padding(.horizontal, 18)
                            .frame(height: 52)
                            .background {
                                RoundedRectangle(cornerRadius: 16, style: .continuous)
                                    .fill(
                                        LinearGradient(
                                            colors: [AlpineTheme.glacierBlue, Color(red: 0.12, green: 0.58, blue: 0.88)],
                                            startPoint: .topLeading,
                                            endPoint: .bottomTrailing
                                        )
                                    )
                                    .shadow(color: AlpineTheme.glacierBlue.opacity(0.3), radius: 8, y: 4)
                            }
                            .foregroundStyle(.black)
                        }
                        .sensoryFeedback(.impact(weight: .medium), trigger: store.resort.gameDay)
                        
                        // Grooming & Snowmaking Fast Actions
                        HStack(spacing: 10) {
                            Button {
                                store.groomAllSlopes()
                            } label: {
                                HStack(spacing: 6) {
                                    Image(systemName: "sparkles")
                                        .foregroundStyle(AlpineTheme.glacierBlue)
                                    Text("Groom Corduroy")
                                        .font(.caption.weight(.bold))
                                }
                                .frame(maxWidth: .infinity)
                                .frame(height: 40)
                                .background {
                                    RoundedRectangle(cornerRadius: 12, style: .continuous)
                                        .fill(.ultraThinMaterial)
                                }
                            }
                            .foregroundStyle(.primary)
                            
                            Button {
                                store.makeSnowOnEquippedSlopes()
                            } label: {
                                HStack(spacing: 6) {
                                    Image(systemName: "snowflake")
                                        .foregroundStyle(store.resort.weather.isSnowmakingOptimal ? .cyan : .secondary)
                                    Text("Snowmaking")
                                        .font(.caption.weight(.bold))
                                }
                                .frame(maxWidth: .infinity)
                                .frame(height: 40)
                                .background {
                                    RoundedRectangle(cornerRadius: 12, style: .continuous)
                                        .fill(.ultraThinMaterial)
                                }
                            }
                            .foregroundStyle(store.resort.weather.isSnowmakingOptimal ? .primary : .secondary)
                            .disabled(!store.resort.weather.isSnowmakingOptimal)
                        }
                    }
                    .padding(.horizontal, 16)
                    
                    // MARK: - 6. Website Modular Dashboard Widgets
                    
                    // Widget A: Trail Map Quick View Card
                    VStack(alignment: .leading, spacing: 10) {
                        HStack {
                            Label("Park City Trail Map", systemImage: "map.fill")
                                .font(.headline)
                                .foregroundStyle(.primary)
                            Spacer()
                            Button {
                                selectedTab = 1 // Switch to Trail Map Tab
                            } label: {
                                Text("Full Map")
                                    .font(.caption.weight(.bold))
                                    .foregroundStyle(AlpineTheme.glacierBlue)
                            }
                        }
                        
                        // Live mini-map thumbnail
                        ZStack(alignment: .bottomLeading) {
                            RoundedRectangle(cornerRadius: 14, style: .continuous)
                                .fill(Color.black.opacity(0.8))
                                .frame(height: 140)
                            
                            // Image background
                            Image("ParkCity")
                                .resizable()
                                .aspectRatio(contentMode: .fill)
                                .frame(height: 140)
                                .clipped()
                                .cornerRadius(14)
                                .opacity(0.7)
                            
                            // Overlay Telemetry
                            HStack(spacing: 8) {
                                Label("\(store.resort.openSlopesCount) Slopes", systemImage: "figure.skiing.downhill")
                                    .font(.caption2.weight(.bold))
                                    .padding(.horizontal, 8)
                                    .padding(.vertical, 4)
                                    .background { Capsule().fill(.ultraThinMaterial) }
                                
                                Label("\(store.resort.runningLiftsCount) Lifts", systemImage: "chairlift.fill")
                                    .font(.caption2.weight(.bold))
                                    .padding(.horizontal, 8)
                                    .padding(.vertical, 4)
                                    .background { Capsule().fill(.ultraThinMaterial) }
                            }
                            .padding(10)
                        }
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // Widget B: Daily Financial Summary
                    VStack(alignment: .leading, spacing: 10) {
                        HStack {
                            Label("Financial Snapshot", systemImage: "coins")
                                .font(.headline)
                            Spacer()
                            Text(store.resort.netDailyProfit >= 0 ? "+€\(store.resort.netDailyProfit.formatted())" : "€\(store.resort.netDailyProfit.formatted())")
                                .font(.subheadline.weight(.bold))
                                .foregroundStyle(store.resort.netDailyProfit >= 0 ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)
                                .monospacedDigit()
                        }
                        
                        Divider().background(Color.white.opacity(0.12))
                        
                        VStack(spacing: 6) {
                            HStack {
                                Text("Lift Ticket Revenue (\(store.resort.totalVisitorsToday) skiers)")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Spacer()
                                Text("+€\(store.resort.dailyTicketRevenue.formatted())")
                                    .font(.caption.weight(.semibold))
                                    .foregroundStyle(AlpineTheme.forestGreen)
                                    .monospacedDigit()
                            }
                            HStack {
                                Text("Lodge & Village Receipts")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Spacer()
                                Text("+€\(store.resort.dailyLodgeRevenue.formatted())")
                                    .font(.caption.weight(.semibold))
                                    .foregroundStyle(AlpineTheme.forestGreen)
                                    .monospacedDigit()
                            }
                            HStack {
                                Text("Staff Payroll (\(store.resort.staff.count) employees)")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Spacer()
                                Text("-€\(store.resort.dailyStaffExpenses.formatted())")
                                    .font(.caption.weight(.semibold))
                                    .foregroundStyle(AlpineTheme.avalancheRed)
                                    .monospacedDigit()
                            }
                            HStack {
                                Text("Lift Power & Slope Upkeep")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Spacer()
                                Text("-€\((store.resort.dailyLiftMaintenance + store.resort.dailySlopeUpkeep).formatted())")
                                    .font(.caption.weight(.semibold))
                                    .foregroundStyle(AlpineTheme.avalancheRed)
                                    .monospacedDigit()
                            }
                        }
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // Widget C: Weather & Snow Conditions
                    VStack(alignment: .leading, spacing: 10) {
                        HStack {
                            Label("Weather & Mountain Snow", systemImage: "cloud.snow.fill")
                                .font(.headline)
                            Spacer()
                            Text(store.resort.weather.condition.rawValue)
                                .font(.caption.weight(.bold))
                                .foregroundStyle(AlpineTheme.glacierBlue)
                        }
                        
                        HStack(spacing: 12) {
                            StatPillView(
                                title: "Base Depth",
                                value: "\(store.resort.weather.baseDepthCm) cm",
                                sfSymbol: "ruler.fill",
                                tintColor: AlpineTheme.glacierBlue
                            )
                            StatPillView(
                                title: "24h Snowfall",
                                value: "+\(store.resort.weather.newSnowfall24hCm) cm",
                                sfSymbol: "snowflake",
                                tintColor: AlpineTheme.forestGreen
                            )
                            StatPillView(
                                title: "Wind Speed",
                                value: "\(store.resort.weather.windSpeedKmh) km/h",
                                sfSymbol: "wind",
                                tintColor: store.resort.weather.windSpeedKmh > 35 ? AlpineTheme.avalancheRed : .primary
                            )
                        }
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // Widget D: Equipment & Groomer Health
                    VStack(alignment: .leading, spacing: 10) {
                        HStack {
                            Label("Equipment & Fleet", systemImage: "wrench.and.screwdriver.fill")
                                .font(.headline)
                            Spacer()
                            Text("PistenBully 600")
                                .font(.caption.weight(.bold))
                                .foregroundStyle(.secondary)
                        }
                        
                        HStack(spacing: 12) {
                            VStack(alignment: .leading, spacing: 4) {
                                HStack {
                                    Text("Groomer Durability")
                                        .font(.caption)
                                        .foregroundStyle(.secondary)
                                    Spacer()
                                    Text("\(store.resort.groomerConditionPct)%")
                                        .font(.caption.weight(.bold))
                                        .foregroundStyle(store.resort.groomerConditionPct > 50 ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)
                                        .monospacedDigit()
                                }
                                GeometryReader { g in
                                    ZStack(alignment: .leading) {
                                        Capsule().fill(Color.primary.opacity(0.1))
                                        Capsule()
                                            .fill(store.resort.groomerConditionPct > 50 ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)
                                            .frame(width: max(8, g.size.width * (Double(store.resort.groomerConditionPct) / 100.0)))
                                    }
                                }
                                .frame(height: 6)
                            }
                            
                            Button {
                                _ = store.repairGroomer()
                            } label: {
                                Text("Overhaul")
                                    .font(.caption.weight(.bold))
                                    .padding(.horizontal, 12)
                                    .padding(.vertical, 6)
                                    .background { Capsule().fill(AlpineTheme.glacierBlue) }
                                    .foregroundStyle(.black)
                            }
                            .disabled(store.resort.groomerConditionPct >= 100 || store.resort.cash < 3200)
                        }
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                }
                .padding(.vertical, 12)
            }
            .navigationTitle("Dashboard")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        Task { _ = await store.syncWithLiveWebsite() }
                    } label: {
                        if store.isCloudSyncing {
                            ProgressView().scaleEffect(0.7)
                        } else {
                            Image(systemName: "arrow.triangle.2.circlepath")
                                .foregroundStyle(AlpineTheme.glacierBlue)
                        }
                    }
                }
            }
        }
    }
}
