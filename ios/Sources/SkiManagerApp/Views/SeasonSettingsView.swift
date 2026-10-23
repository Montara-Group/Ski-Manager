import Foundation
import SwiftUI

public struct SeasonSettingsView: View {
    @Environment(ResortStore.self) private var store
    
    @State private var serverStatusText: String = "Operating Locally (Offline-First)"
    @State private var isServerOnline: Bool = true
    @State private var isServerMaintenance: Bool = false
    @State private var leaderboard: [LeaderboardEntry] = []
    @State private var isLoadingLeaderboard: Bool = false
    @State private var showingResetConfirmation: Bool = false
    @State private var fastForwardSuccess: Bool = false
    
    public init() {}
    
    public var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 16) {
                    // Top Stats Bar
                    ResortStatsBarView()
                        .padding(.horizontal, 16)
                    
                    // MARK: - 1. Local App Offline Status Card
                    VStack(alignment: .leading, spacing: 12) {
                        HStack(spacing: 12) {
                            ZStack {
                                Circle()
                                    .fill(AlpineTheme.forestGreen.opacity(0.18))
                                    .frame(width: 44, height: 44)
                                Image(systemName: "internaldrive.fill")
                                    .font(.title3)
                                    .foregroundStyle(AlpineTheme.forestGreen)
                            }
                            
                            VStack(alignment: .leading, spacing: 2) {
                                Text("Local Offline Simulation Engine")
                                    .font(.headline)
                                Text("Active Engine: Native Swift 6")
                                    .font(.caption2)
                                    .foregroundStyle(.secondary)
                            }
                            
                            Spacer()
                            
                            Text("LOCAL")
                                .font(.caption2.weight(.bold))
                                .padding(.horizontal, 8)
                                .padding(.vertical, 4)
                                .background { Capsule().fill(AlpineTheme.forestGreen.opacity(0.2)) }
                                .foregroundStyle(AlpineTheme.forestGreen)
                        }
                        
                        Divider().background(Color.white.opacity(0.1))
                        
                        VStack(spacing: 6) {
                            HStack {
                                Text("Storage Location")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Spacer()
                                Text("Device Sandboxed Documents")
                                    .font(.caption.weight(.semibold))
                            }
                            HStack {
                                Text("Save File")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Spacer()
                                Text(ResortStore.localSaveFileSizeFormatted)
                                    .font(.caption.weight(.semibold))
                                    .monospacedDigit()
                            }
                            HStack {
                                Text("Network Requirement")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Spacer()
                                Text("None (100% Offline Capable)")
                                    .font(.caption.weight(.semibold))
                                    .foregroundStyle(AlpineTheme.forestGreen)
                            }
                        }
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // MARK: - 2. Fast Simulation Tools
                    VStack(alignment: .leading, spacing: 10) {
                        Text("Simulation Controls")
                            .font(.headline)
                            .padding(.horizontal, 16)
                        
                        Button {
                            for _ in 1...5 {
                                store.advanceDay()
                            }
                            withAnimation(AlpineTheme.bouncySpring) {
                                fastForwardSuccess = true
                            }
                        } label: {
                            HStack {
                                Image(systemName: "forward.end.fill")
                                Text("Fast Forward 5 Game Days")
                                Spacer()
                                Text("+5 Days")
                                    .font(.caption2.weight(.bold))
                                    .padding(.horizontal, 8)
                                    .padding(.vertical, 4)
                                    .background { Capsule().fill(Color.black.opacity(0.2)) }
                            }
                            .font(.subheadline.weight(.semibold))
                        }
                        .buttonStyle(AlpineGlassButtonStyle(isProminent: true))
                        .padding(.horizontal, 16)
                        .sensoryFeedback(.impact(weight: .heavy), trigger: fastForwardSuccess)
                    }
                    
                    // MARK: - 3. Season 2 Expansion Banner
                    HStack(spacing: 14) {
                        ZStack {
                            RoundedRectangle(cornerRadius: 14, style: .continuous)
                                .fill(AlpineTheme.peakAmber.opacity(0.18))
                                .frame(width: 44, height: 44)
                            Image(systemName: "mountain.2.fill")
                                .font(.title3)
                                .foregroundStyle(AlpineTheme.peakAmber)
                        }
                        
                        VStack(alignment: .leading, spacing: 2) {
                            Text("Season 2: Park City Expansion")
                                .font(.headline)
                            Text("Sector 2 Advanced Peaks, powder chutes, and Jupiter Tram.")
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        }
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // MARK: - 4. Cloud Sync (Optional)
                    VStack(alignment: .leading, spacing: 12) {
                        HStack {
                            Label("Cloud Sync (Optional)", systemImage: "icloud.fill")
                                .font(.headline)
                            Spacer()
                            Text(serverStatusText)
                                .font(.caption2.weight(.bold))
                                .foregroundStyle(isServerOnline ? AlpineTheme.forestGreen : AlpineTheme.peakAmber)
                        }
                        
                        Button {
                            Task {
                                _ = await store.syncWithLiveWebsite()
                                await performHealthCheck()
                            }
                        } label: {
                            HStack {
                                if store.isCloudSyncing {
                                    ProgressView().tint(.white).padding(.trailing, 4)
                                } else {
                                    Image(systemName: "arrow.triangle.2.circlepath")
                                }
                                Text(store.isCloudSyncing ? "Syncing..." : "Sync with ski-manager.net")
                                Spacer()
                            }
                            .font(.caption.weight(.semibold))
                        }
                        .buttonStyle(AlpineGlassButtonStyle())
                        .disabled(store.isCloudSyncing)
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // MARK: - 5. Factory Reset to Demo Mountain
                    VStack(alignment: .leading, spacing: 10) {
                        Text("Data Management")
                            .font(.headline)
                            .padding(.horizontal, 16)
                        
                        Button(role: .destructive) {
                            showingResetConfirmation = true
                        } label: {
                            HStack {
                                Image(systemName: "arrow.counterclockwise.circle.fill")
                                Text("Reset to Factory Demo Mountain")
                                Spacer()
                            }
                            .frame(maxWidth: .infinity)
                        }
                        .buttonStyle(AlpineGlassButtonStyle(isDestructive: true))
                        .padding(.horizontal, 16)
                    }
                }
                .padding(.vertical, 12)
            }
            .navigationTitle("Season & Settings")
            .navigationBarTitleDisplayMode(.inline)
            .confirmationDialog(
                "Reset Mountain to Factory Starter?",
                isPresented: $showingResetConfirmation,
                titleVisibility: .visible
            ) {
                Button("Reset to Factory Mountain", role: .destructive) {
                    store.resetToTemplate()
                }
                Button("Cancel", role: .cancel) {}
            } message: {
                Text("This will restore the authentic starting setup matching Demo.php with 4 lifts, 8 slopes, 3 buildings, 4 staff, and €250,000 cash.")
            }
            .task {
                await performHealthCheck()
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
            serverStatusText = "Offline Mode Active"
        }
    }
}
