import SwiftUI

public struct CloudSyncView: View {
    @Environment(ResortStore.self) private var store
    
    @State private var serverStatusText: String = "Checking ski-manager.net..."
    @State private var isServerOnline: Bool = true
    @State private var isServerMaintenance: Bool = false
    @State private var leaderboard: [LeaderboardEntry] = []
    @State private var isLoadingLeaderboard: Bool = false
    @State private var showingResetConfirmation: Bool = false
    
    public init() {}
    
    public var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 20) {
                    // Cloud Connection Status Hero
                    VStack(alignment: .leading, spacing: 14) {
                        HStack(spacing: 12) {
                            ZStack {
                                Circle()
                                    .fill((isServerMaintenance ? AlpineTheme.peakAmber : (isServerOnline ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)).opacity(0.2))
                                    .frame(width: 44, height: 44)
                                
                                Image(systemName: isServerMaintenance ? "wrench.and.screwdriver.fill" : (isServerOnline ? "icloud.fill" : "icloud.slash.fill"))
                                    .font(.title3)
                                    .foregroundStyle(isServerMaintenance ? AlpineTheme.peakAmber : (isServerOnline ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed))
                            }
                            
                            VStack(alignment: .leading, spacing: 2) {
                                Text("Ski Manager Cloud Sync")
                                    .font(.headline)
                                Text("Endpoint: https://ski-manager.net")
                                    .font(.caption2)
                                    .foregroundStyle(.secondary)
                            }
                            
                            Spacer()
                            
                            Button {
                                Task { await performHealthCheck() }
                            } label: {
                                Image(systemName: "arrow.clockwise")
                                    .font(.subheadline)
                                    .foregroundStyle(AlpineTheme.glacierBlue)
                                    .frame(width: 44, height: 44)
                            }
                        }
                        
                        // Status Badge
                        HStack(spacing: 6) {
                            Circle()
                                .fill(isServerMaintenance ? AlpineTheme.peakAmber : (isServerOnline ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed))
                                .frame(width: 8, height: 8)
                            Text(serverStatusText)
                                .font(.caption.weight(.semibold))
                                .foregroundStyle(.primary)
                        }
                        .padding(.horizontal, 10)
                        .padding(.vertical, 6)
                        .background {
                            Capsule().fill(.ultraThinMaterial)
                        }
                        
                        Text("Ski Manager runs seamlessly offline with full local persistence. When network access is available, resort telemetry and leaderboard standings synchronize automatically.")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // Season 2 Roadmap Banner
                    HStack(spacing: 14) {
                        ZStack {
                            RoundedRectangle(cornerRadius: 14, style: .continuous)
                                .fill(AlpineTheme.peakAmber.opacity(0.15))
                                .frame(width: 44, height: 44)
                            
                            Image(systemName: "mountain.2.fill")
                                .font(.title3)
                                .foregroundStyle(AlpineTheme.peakAmber)
                        }
                        
                        VStack(alignment: .leading, spacing: 2) {
                            Text("Season 2: Park City Expansion")
                                .font(.headline)
                                .foregroundStyle(.primary)
                            Text("Sector 2 Advanced Peaks, powder bowls, and high-speed tramways.")
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        }
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // Global Pioneer Leaderboard
                    VStack(alignment: .leading, spacing: 12) {
                        HStack {
                            Text("Global Pioneer Leaderboard")
                                .font(.headline)
                                .foregroundStyle(.primary)
                            
                            Spacer()
                            
                            if isLoadingLeaderboard {
                                ProgressView()
                                    .scaleEffect(0.8)
                            }
                        }
                        .padding(.horizontal, 16)
                        
                        LazyVStack(spacing: 8) {
                            ForEach(leaderboard) { entry in
                                HStack(spacing: 12) {
                                    // Rank Badge
                                    Text("#\(entry.rankPosition)")
                                        .font(.subheadline.weight(.bold))
                                        .foregroundStyle(entry.rankPosition <= 3 ? AlpineTheme.peakAmber : .secondary)
                                        .frame(width: 32, alignment: .leading)
                                        .monospacedDigit()
                                    
                                    VStack(alignment: .leading, spacing: 2) {
                                        Text(entry.username)
                                            .font(.subheadline.weight(.semibold))
                                        if let resort = entry.resortName {
                                            Text(resort)
                                                .font(.caption2)
                                                .foregroundStyle(.secondary)
                                        }
                                    }
                                    
                                    Spacer()
                                    
                                    VStack(alignment: .trailing, spacing: 2) {
                                        Text("€\(entry.cash.formatted())")
                                            .font(.subheadline.weight(.bold))
                                            .foregroundStyle(AlpineTheme.forestGreen)
                                            .monospacedDigit()
                                        Text("\(entry.reputation)% Rep")
                                            .font(.caption2)
                                            .foregroundStyle(.secondary)
                                    }
                                }
                                .padding(12)
                                .background {
                                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                                        .fill(.ultraThinMaterial)
                                }
                            }
                        }
                        .padding(.horizontal, 16)
                    }
                    
                    // Reset / Management Section
                    VStack(alignment: .leading, spacing: 12) {
                        Text("Resort Management Tools")
                            .font(.headline)
                            .foregroundStyle(.primary)
                            .padding(.horizontal, 16)
                        
                        Button(role: .destructive) {
                            showingResetConfirmation = true
                        } label: {
                            HStack {
                                Image(systemName: "arrow.counterclockwise.circle.fill")
                                Text("Reset to Park City Template")
                                Spacer()
                            }
                            .frame(maxWidth: .infinity)
                        }
                        .buttonStyle(AlpineGlassButtonStyle(isDestructive: true))
                        .padding(.horizontal, 16)
                    }
                }
                .padding(.vertical, 16)
            }
            .navigationTitle("Sync & Season")
            .navigationBarTitleDisplayMode(.large)
            .task {
                await performHealthCheck()
                await loadLeaderboard()
            }
            .confirmationDialog(
                "Reset Mountain to Default Template?",
                isPresented: $showingResetConfirmation,
                titleVisibility: .visible
            ) {
                Button("Reset Mountain", role: .destructive) {
                    store.resetToTemplate()
                }
                Button("Cancel", role: .cancel) {}
            } message: {
                Text("This will restore the starting Park City layout with 7 lifts, 7 trails, and standard capital.")
            }
        }
    }
    
    private func performHealthCheck() async {
        do {
            let result = try await SkiManagerAPIClient.shared.checkServerHealth()
            isServerOnline = result.isHealthy
            isServerMaintenance = result.isMaintenance
            serverStatusText = result.message
        } catch {
            isServerOnline = false
            isServerMaintenance = false
            serverStatusText = "Operating in Offline Simulation Mode"
        }
    }
    
    private func loadLeaderboard() async {
        isLoadingLeaderboard = true
        defer { isLoadingLeaderboard = false }
        do {
            leaderboard = try await SkiManagerAPIClient.shared.fetchLeaderboard()
        } catch {
            leaderboard = SkiManagerAPIClient.fallbackPioneerLeaderboard
        }
    }
}
