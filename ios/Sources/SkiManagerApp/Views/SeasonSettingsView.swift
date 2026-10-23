import Foundation
import SwiftUI

public struct SeasonSettingsView: View {
    @Environment(ResortStore.self) private var store
    
    @State private var showingResetConfirmation: Bool = false
    @State private var fastForwardSuccess: Bool = false
    @State private var isRefreshingLeaderboard: Bool = false
    
    public init() {}
    
    public var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 16) {
                    // Top Snapshot Bar
                    ResortStatsBarView()
                        .padding(.horizontal, 16)
                    
                    // MARK: - 1. Live Online Server Connection Card
                    VStack(alignment: .leading, spacing: 12) {
                        HStack(spacing: 12) {
                            ZStack {
                                Circle()
                                    .fill((store.isOnline ? AlpineTheme.forestGreen : AlpineTheme.peakAmber).opacity(0.18))
                                    .frame(width: 44, height: 44)
                                Image(systemName: store.isOnline ? "globe.americas.fill" : "wifi.slash")
                                    .font(.title3)
                                    .foregroundStyle(store.isOnline ? AlpineTheme.forestGreen : AlpineTheme.peakAmber)
                            }
                            
                            VStack(alignment: .leading, spacing: 2) {
                                Text("Ski Manager Online Network")
                                    .font(.headline)
                                Text("Endpoint: https://ski-manager.net")
                                    .font(.caption2)
                                    .foregroundStyle(.secondary)
                            }
                            
                            Spacer()
                            
                            HStack(spacing: 5) {
                                Circle()
                                    .fill(store.isOnline ? AlpineTheme.forestGreen : AlpineTheme.peakAmber)
                                    .frame(width: 8, height: 8)
                                Text(store.isOnline ? "ONLINE" : "CACHED")
                                    .font(.caption2.weight(.bold))
                                    .foregroundStyle(store.isOnline ? AlpineTheme.forestGreen : AlpineTheme.peakAmber)
                            }
                            .padding(.horizontal, 8)
                            .padding(.vertical, 4)
                            .background { Capsule().fill(Color.primary.opacity(0.06)) }
                        }
                        
                        Divider().background(Color.white.opacity(0.1))
                        
                        VStack(spacing: 8) {
                            Button {
                                Task {
                                    _ = await store.syncWithLiveWebsite()
                                }
                            } label: {
                                HStack {
                                    if store.isCloudSyncing {
                                        ProgressView().tint(.white).padding(.trailing, 4)
                                    } else {
                                        Image(systemName: "arrow.triangle.2.circlepath")
                                    }
                                    Text(store.isCloudSyncing ? "Synchronizing Mountain..." : "Sync with ski-manager.net Now")
                                    Spacer()
                                    if let lastSync = store.lastSyncDate {
                                        Text(lastSync.formatted(date: .omitted, time: .shortened))
                                            .font(.caption2)
                                            .foregroundStyle(.secondary)
                                    }
                                }
                                .font(.subheadline.weight(.semibold))
                            }
                            .buttonStyle(AlpineGlassButtonStyle())
                            .disabled(store.isCloudSyncing)
                            
                            HStack {
                                Text("All changes sync automatically in the background with ski-manager.net while maintaining instant offline fallback.")
                                    .font(.caption2)
                                    .foregroundStyle(.secondary)
                            }
                        }
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // MARK: - 2. Live Global Pioneer Leaderboard
                    VStack(alignment: .leading, spacing: 12) {
                        HStack {
                            Label("Global Pioneer Leaderboard", systemImage: "trophy.fill")
                                .font(.headline)
                                .foregroundStyle(.primary)
                            
                            Spacer()
                            
                            Button {
                                Task {
                                    isRefreshingLeaderboard = true
                                    if let board = try? await SkiManagerAPIClient.shared.fetchLeaderboard() {
                                        store.onlineLeaderboard = board
                                    }
                                    isRefreshingLeaderboard = false
                                }
                            } label: {
                                if isRefreshingLeaderboard {
                                    ProgressView().scaleEffect(0.7)
                                } else {
                                    Image(systemName: "arrow.clockwise")
                                        .font(.caption.weight(.bold))
                                        .foregroundStyle(AlpineTheme.glacierBlue)
                                }
                            }
                        }
                        .padding(.horizontal, 16)
                        
                        LazyVStack(spacing: 8) {
                            ForEach(store.onlineLeaderboard.prefix(10)) { entry in
                                HStack(spacing: 12) {
                                    // Rank
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
                                .padding(.horizontal, 16)
                            }
                        }
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
                    
                    // MARK: - 4. Local Simulation & Storage Diagnostics
                    VStack(alignment: .leading, spacing: 10) {
                        Text("Local Diagnostics & Simulation")
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
                                Text("Advance 5 Days (Fast Sim)")
                                Spacer()
                                Text("+5 Days")
                                    .font(.caption2.weight(.bold))
                                    .padding(.horizontal, 8)
                                    .padding(.vertical, 4)
                                    .background { Capsule().fill(Color.black.opacity(0.2)) }
                            }
                            .font(.subheadline.weight(.semibold))
                        }
                        .buttonStyle(AlpineGlassButtonStyle())
                        .padding(.horizontal, 16)
                        .sensoryFeedback(.impact(weight: .heavy), trigger: fastForwardSuccess)
                        
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
            .navigationTitle("Season & Online")
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
        }
    }
}
