import Foundation
import SwiftUI

public struct MountainView: View {
    @Environment(ResortStore.self) private var store
    
    @State private var selectedTab: Int = 0 // 0: Interactive Map, 1: Slopes Directory, 2: Lift Fleet
    @State private var showingAddLift: Bool = false
    @State private var showingAddSlope: Bool = false
    @State private var selectedDifficultyFilter: String = "All"
    
    public init() {}
    
    private var builtRealSlopes: [RealMapSegment] {
        store.resort.realSegments.filter { $0.isSlope && $0.isBuilt }
    }
    
    private var builtRealLifts: [RealMapSegment] {
        store.resort.realSegments.filter { $0.isLift && $0.isBuilt }
    }
    
    private var filteredSlopes: [RealMapSegment] {
        if selectedDifficultyFilter == "All" {
            return builtRealSlopes
        }
        return builtRealSlopes.filter {
            $0.difficulty?.lowercased() == selectedDifficultyFilter.lowercased()
        }
    }
    
    public var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                // View Selector Bar
                Picker("Mountain View", selection: $selectedTab) {
                    Label("Trail Map", systemImage: "map.fill").tag(0)
                    Label("Slopes (\(builtRealSlopes.count))", systemImage: "figure.skiing.downhill").tag(1)
                    Label("Lifts (\(builtRealLifts.count))", systemImage: "chairlift.fill").tag(2)
                }
                .pickerStyle(.segmented)
                .padding(.horizontal, 16)
                .padding(.top, 10)
                .padding(.bottom, 6)
                
                if selectedTab == 0 {
                    // TAB 0: Interactive Authentic Trail Map (ParkCity.jpg + Leaflet Segments)
                    VStack(spacing: 0) {
                        InteractiveTrailMapView()
                        
                        // Summary Stats Bar Below Map
                        HStack(spacing: 10) {
                            StatPillView(
                                title: "Open Slopes",
                                value: "\(builtRealSlopes.filter { $0.isOpen }.count)/\(builtRealSlopes.count)",
                                sfSymbol: "figure.skiing.downhill",
                                tintColor: AlpineTheme.forestGreen
                            )
                            
                            StatPillView(
                                title: "Active Lifts",
                                value: "\(builtRealLifts.filter { $0.isOpen }.count)/\(builtRealLifts.count)",
                                sfSymbol: "chairlift.fill",
                                tintColor: AlpineTheme.glacierBlue
                            )
                            
                            StatPillView(
                                title: "Skiable Terrain",
                                value: "\(String(format: "%.1f", store.resort.totalSkiableLengthKm)) km",
                                sfSymbol: "mountain.2.fill",
                                tintColor: AlpineTheme.peakAmber
                            )
                        }
                        .padding(.horizontal, 16)
                        .padding(.vertical, 8)
                    }
                } else if selectedTab == 1 {
                    // TAB 1: Slopes Directory
                    ScrollView {
                        VStack(spacing: 16) {
                            // Difficulty Filter Pills
                            ScrollView(.horizontal, showsIndicators: false) {
                                HStack(spacing: 8) {
                                    ForEach(["All", "Green", "Blue", "Black"], id: \.self) { filter in
                                        Button {
                                            withAnimation(AlpineTheme.snappySpring) {
                                                selectedDifficultyFilter = filter
                                            }
                                        } label: {
                                            Text(filter)
                                                .font(.caption.weight(.semibold))
                                                .padding(.horizontal, 12)
                                                .padding(.vertical, 6)
                                                .background {
                                                    if selectedDifficultyFilter == filter {
                                                        Capsule().fill(AlpineTheme.glacierBlue)
                                                    } else {
                                                        Capsule().fill(.ultraThinMaterial)
                                                    }
                                                }
                                                .foregroundStyle(selectedDifficultyFilter == filter ? .black : .primary)
                                        }
                                    }
                                }
                                .padding(.horizontal, 16)
                            }
                            
                            // Slope List Items
                            LazyVStack(spacing: 10) {
                                ForEach(filteredSlopes) { slope in
                                    RealSlopeRowView(segment: slope) {
                                        store.toggleRealSegmentOpen(id: slope.id)
                                    }
                                }
                            }
                            .padding(.horizontal, 16)
                        }
                        .padding(.vertical, 12)
                    }
                } else {
                    // TAB 2: Lift Fleet Directory
                    ScrollView {
                        VStack(spacing: 16) {
                            LazyVStack(spacing: 10) {
                                ForEach(builtRealLifts) { lift in
                                    RealLiftRowView(segment: lift) {
                                        store.toggleRealSegmentOpen(id: lift.id)
                                    }
                                }
                            }
                            .padding(.horizontal, 16)
                        }
                        .padding(.vertical, 12)
                    }
                }
            }
            .navigationTitle("Trail Map")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Menu {
                        Button {
                            store.groomAllSlopes()
                        } label: {
                            Label("Groom All Slopes", systemImage: "sparkles")
                        }
                        Button {
                            store.makeSnowOnEquippedSlopes()
                        } label: {
                            Label("Make Snow", systemImage: "snowflake")
                        }
                        Divider()
                        Button {
                            showingAddLift = true
                        } label: {
                            Label("Build Custom Lift", systemImage: "chairlift.fill")
                        }
                        Button {
                            showingAddSlope = true
                        } label: {
                            Label("Carve Custom Slope", systemImage: "figure.skiing.downhill")
                        }
                    } label: {
                        Image(systemName: "ellipsis.circle.fill")
                            .symbolRenderingMode(.hierarchical)
                            .foregroundStyle(AlpineTheme.glacierBlue)
                            .font(.title3)
                            .frame(width: 44, height: 44)
                    }
                }
            }
            .sheet(isPresented: $showingAddLift) {
                AddLiftSheet()
                    .presentationDetents([.medium, .large])
                    .presentationDragIndicator(.visible)
            }
            .sheet(isPresented: $showingAddSlope) {
                AddSlopeSheet()
                    .presentationDetents([.medium, .large])
                    .presentationDragIndicator(.visible)
            }
        }
    }
}

// MARK: - Authentic Real Map Slope Row

public struct RealSlopeRowView: View {
    public let segment: RealMapSegment
    public let onToggle: () -> Void
    
    public var body: some View {
        HStack(spacing: 14) {
            // Difficulty Badge Icon
            ZStack {
                RoundedRectangle(cornerRadius: 10, style: .continuous)
                    .fill(segment.strokeColor.opacity(0.18))
                    .frame(width: 40, height: 40)
                
                Image(systemName: segment.sfSymbol)
                    .foregroundStyle(segment.strokeColor)
                    .font(.body.weight(.bold))
            }
            
            VStack(alignment: .leading, spacing: 3) {
                Text(segment.name)
                    .font(.subheadline.weight(.semibold))
                    .foregroundStyle(.primary)
                
                HStack(spacing: 6) {
                    Text(segment.difficultyDisplay)
                        .font(.caption2.weight(.bold))
                        .foregroundStyle(segment.strokeColor)
                    
                    Text("•")
                        .font(.caption2)
                        .foregroundStyle(.secondary)
                    
                    Text("\(segment.lengthMeters) m")
                        .font(.caption2)
                        .foregroundStyle(.secondary)
                        .monospacedDigit()
                    
                    Text("•")
                        .font(.caption2)
                        .foregroundStyle(.secondary)
                    
                    Text("\(segment.groomingPercent)% Groomed")
                        .font(.caption2)
                        .foregroundStyle(AlpineTheme.glacierBlue)
                        .monospacedDigit()
                }
            }
            
            Spacer()
            
            Button(action: onToggle) {
                Text(segment.isOpen ? "Open" : "Closed")
                    .font(.caption.weight(.bold))
                    .padding(.horizontal, 10)
                    .padding(.vertical, 5)
                    .background {
                        Capsule().fill(segment.isOpen ? AlpineTheme.forestGreen.opacity(0.2) : AlpineTheme.avalancheRed.opacity(0.2))
                    }
                    .foregroundStyle(segment.isOpen ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)
            }
        }
        .padding(12)
        .background {
            RoundedRectangle(cornerRadius: 14, style: .continuous)
                .fill(.ultraThinMaterial)
        }
    }
}

// MARK: - Authentic Real Map Lift Row

public struct RealLiftRowView: View {
    public let segment: RealMapSegment
    public let onToggle: () -> Void
    
    public var body: some View {
        HStack(spacing: 14) {
            ZStack {
                RoundedRectangle(cornerRadius: 10, style: .continuous)
                    .fill(AlpineTheme.peakAmber.opacity(0.18))
                    .frame(width: 40, height: 40)
                
                Image(systemName: "chairlift.fill")
                    .foregroundStyle(AlpineTheme.peakAmber)
                    .font(.body.weight(.bold))
            }
            
            VStack(alignment: .leading, spacing: 3) {
                Text(segment.name)
                    .font(.subheadline.weight(.semibold))
                    .foregroundStyle(.primary)
                
                HStack(spacing: 6) {
                    Text("\(segment.lengthMeters) m")
                        .font(.caption2)
                        .foregroundStyle(.secondary)
                        .monospacedDigit()
                    
                    Text("•")
                        .font(.caption2)
                        .foregroundStyle(.secondary)
                    
                    Text("\(segment.capacity) skiers/hr")
                        .font(.caption2)
                        .foregroundStyle(.secondary)
                        .monospacedDigit()
                    
                    Text("•")
                        .font(.caption2)
                        .foregroundStyle(.secondary)
                    
                    Text("\(segment.conditionPercent)% Health")
                        .font(.caption2)
                        .foregroundStyle(segment.conditionPercent > 60 ? AlpineTheme.forestGreen : AlpineTheme.peakAmber)
                        .monospacedDigit()
                }
            }
            
            Spacer()
            
            Button(action: onToggle) {
                Text(segment.isOpen ? "Running" : "Stopped")
                    .font(.caption.weight(.bold))
                    .padding(.horizontal, 10)
                    .padding(.vertical, 5)
                    .background {
                        Capsule().fill(segment.isOpen ? AlpineTheme.forestGreen.opacity(0.2) : AlpineTheme.avalancheRed.opacity(0.2))
                    }
                    .foregroundStyle(segment.isOpen ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)
            }
        }
        .padding(12)
        .background {
            RoundedRectangle(cornerRadius: 14, style: .continuous)
                .fill(.ultraThinMaterial)
        }
    }
}
