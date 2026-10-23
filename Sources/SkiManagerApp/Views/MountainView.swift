import SwiftUI

public struct MountainView: View {
    @Environment(ResortStore.self) private var store
    
    @State private var showingAddLift: Bool = false
    @State private var showingAddSlope: Bool = false
    @State private var selectedFilter: String = "All"
    
    public init() {}
    
    private var filteredLifts: [Lift] {
        store.resort.lifts.filter { $0.sectorNumber == store.selectedSector }
    }
    
    private var filteredSlopes: [Slope] {
        let sectorSlopes = store.resort.slopes.filter { $0.sectorNumber == store.selectedSector }
        if selectedFilter == "All" {
            return sectorSlopes
        }
        return sectorSlopes.filter { $0.difficulty.shortLabel == selectedFilter }
    }
    
    public var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 20) {
                    // Sector Switcher Segmented Control
                    Picker("Sector", selection: Binding(
                        get: { store.selectedSector },
                        set: { store.selectedSector = $0 }
                    )) {
                        Text("Sector 1: Base Village").tag(1)
                        Text("Sector 2: Advanced Peaks").tag(2)
                    }
                    .pickerStyle(.segmented)
                    .padding(.horizontal, 16)
                    
                    // Mountain Schematic Visualization Card
                    MountainSchematicCard(
                        sector: store.selectedSector,
                        lifts: filteredLifts,
                        slopes: filteredSlopes,
                        weather: store.resort.weather
                    )
                    .padding(.horizontal, 16)
                    
                    // Sector Telemetry Pills
                    HStack(spacing: 12) {
                        StatPillView(
                            title: "Open Slopes",
                            value: "\(filteredSlopes.filter { $0.isOpen }.count)/\(filteredSlopes.count)",
                            sfSymbol: "figure.skiing.downhill",
                            tintColor: AlpineTheme.forestGreen
                        )
                        
                        StatPillView(
                            title: "Active Lifts",
                            value: "\(filteredLifts.filter { $0.status == .running }.count)/\(filteredLifts.count)",
                            sfSymbol: "chairlift.fill",
                            tintColor: AlpineTheme.glacierBlue
                        )
                    }
                    .padding(.horizontal, 16)
                    
                    // Slopes Section with Filter
                    VStack(alignment: .leading, spacing: 12) {
                        HStack {
                            Text("Ski Trails (\(filteredSlopes.count))")
                                .font(.headline)
                                .foregroundStyle(.primary)
                            
                            Spacer()
                            
                            Button {
                                showingAddSlope = true
                            } label: {
                                Label("Carve Slope", systemImage: "plus.circle.fill")
                                    .font(.subheadline.weight(.semibold))
                                    .foregroundStyle(AlpineTheme.glacierBlue)
                            }
                        }
                        .padding(.horizontal, 16)
                        
                        // Difficulty Filter Pills
                        ScrollView(.horizontal, showsIndicators: false) {
                            HStack(spacing: 8) {
                                ForEach(["All", "Green", "Blue", "Black", "Double Black", "Park"], id: \.self) { filter in
                                    Button {
                                        withAnimation(AlpineTheme.snappySpring) {
                                            selectedFilter = filter
                                        }
                                    } label: {
                                        Text(filter)
                                            .font(.caption.weight(.semibold))
                                            .padding(.horizontal, 12)
                                            .padding(.vertical, 6)
                                            .background {
                                                if selectedFilter == filter {
                                                    Capsule().fill(AlpineTheme.glacierBlue)
                                                } else {
                                                    Capsule().fill(.ultraThinMaterial)
                                                }
                                            }
                                            .foregroundStyle(selectedFilter == filter ? .black : .primary)
                                    }
                                }
                            }
                            .padding(.horizontal, 16)
                        }
                        
                        LazyVStack(spacing: 10) {
                            ForEach(filteredSlopes) { slope in
                                SlopeRowView(slope: slope) {
                                    store.toggleSlopeStatus(id: slope.id)
                                }
                            }
                        }
                        .padding(.horizontal, 16)
                    }
                    
                    // Lifts Section
                    VStack(alignment: .leading, spacing: 12) {
                        HStack {
                            Text("Lift Fleet (\(filteredLifts.count))")
                                .font(.headline)
                                .foregroundStyle(.primary)
                            
                            Spacer()
                            
                            Button {
                                showingAddLift = true
                            } label: {
                                Label("Build Lift", systemImage: "plus.circle.fill")
                                    .font(.subheadline.weight(.semibold))
                                    .foregroundStyle(AlpineTheme.glacierBlue)
                            }
                        }
                        .padding(.horizontal, 16)
                        
                        LazyVStack(spacing: 10) {
                            ForEach(filteredLifts) { lift in
                                LiftRowView(lift: lift) {
                                    store.toggleLiftStatus(id: lift.id)
                                }
                            }
                        }
                        .padding(.horizontal, 16)
                    }
                }
                .padding(.vertical, 16)
            }
            .navigationTitle("Trail Map")
            .navigationBarTitleDisplayMode(.large)
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Menu {
                        Button {
                            showingAddLift = true
                        } label: {
                            Label("Build Lift", systemImage: "chairlift.fill")
                        }
                        Button {
                            showingAddSlope = true
                        } label: {
                            Label("Carve Slope", systemImage: "figure.skiing.downhill")
                        }
                    } label: {
                        Image(systemName: "plus.circle.fill")
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

// MARK: - Mountain Visual Schematic Canvas

public struct MountainSchematicCard: View {
    public let sector: Int
    public let lifts: [Lift]
    public let slopes: [Slope]
    public let weather: MountainWeather
    
    public var body: some View {
        ZStack {
            // Mountain Background Gradient
            LinearGradient(
                colors: sector == 1
                    ? [Color(red: 0.10, green: 0.18, blue: 0.32), Color(red: 0.05, green: 0.09, blue: 0.18)]
                    : [Color(red: 0.18, green: 0.14, blue: 0.30), Color(red: 0.07, green: 0.05, blue: 0.15)],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )
            
            // Stylized Peak Contours & Ski Map Lines
            Canvas { context, size in
                let w = size.width
                let h = size.height
                
                // Summit Ridgelines
                var peakPath = Path()
                peakPath.move(to: CGPoint(x: 0, y: h * 0.7))
                peakPath.addCurve(
                    to: CGPoint(x: w * 0.45, y: h * 0.18),
                    control1: CGPoint(x: w * 0.15, y: h * 0.45),
                    control2: CGPoint(x: w * 0.3, y: h * 0.22)
                )
                peakPath.addCurve(
                    to: CGPoint(x: w, y: h * 0.65),
                    control1: CGPoint(x: w * 0.65, y: h * 0.15),
                    control2: CGPoint(x: w * 0.85, y: h * 0.45)
                )
                peakPath.addLine(to: CGPoint(x: w, y: h))
                peakPath.addLine(to: CGPoint(x: 0, y: h))
                peakPath.closeSubpath()
                
                context.fill(
                    peakPath,
                    with: .linearGradient(
                        Gradient(colors: [Color.white.opacity(0.18), Color.white.opacity(0.04)]),
                        startPoint: CGPoint(x: w * 0.5, y: 0),
                        endPoint: CGPoint(x: w * 0.5, y: h)
                    )
                )
                
                // Front Ridge Line
                var frontRidge = Path()
                frontRidge.move(to: CGPoint(x: 0, y: h * 0.85))
                frontRidge.addCurve(
                    to: CGPoint(x: w * 0.75, y: h * 0.4),
                    control1: CGPoint(x: w * 0.25, y: h * 0.65),
                    control2: CGPoint(x: w * 0.5, y: h * 0.45)
                )
                frontRidge.addCurve(
                    to: CGPoint(x: w, y: h * 0.8),
                    control1: CGPoint(x: w * 0.88, y: h * 0.4),
                    control2: CGPoint(x: w * 0.95, y: h * 0.6)
                )
                frontRidge.addLine(to: CGPoint(x: w, y: h))
                frontRidge.addLine(to: CGPoint(x: 0, y: h))
                frontRidge.closeSubpath()
                
                context.fill(frontRidge, with: .color(Color.white.opacity(0.08)))
                
                // Lift Cables
                for i in 0..<min(lifts.count, 4) {
                    let startX = w * (0.2 + (Double(i) * 0.2))
                    let startY = h * 0.9
                    let endX = w * (0.3 + (Double(i) * 0.18))
                    let endY = h * (0.25 + (Double(i) * 0.08))
                    
                    var cable = Path()
                    cable.move(to: CGPoint(x: startX, y: startY))
                    cable.addLine(to: CGPoint(x: endX, y: endY))
                    
                    context.stroke(
                        cable,
                        with: .color(AlpineTheme.glacierBlue.opacity(0.7)),
                        style: StrokeStyle(lineWidth: 1.5, dash: [4, 4])
                    )
                }
            }
            
            // Overlay Sector Tag & Weather Badge
            VStack {
                HStack {
                    VStack(alignment: .leading, spacing: 2) {
                        Text(sector == 1 ? "SECTOR 1: BASE VILLAGE" : "SECTOR 2: ADVANCED PEAKS")
                            .font(.system(size: 11, weight: .black))
                            .foregroundStyle(sector == 1 ? AlpineTheme.glacierBlue : AlpineTheme.peakAmber)
                            .tracking(1.2)
                        
                        Text(sector == 1 ? "Park City Mountain Base" : "Jupiter & McConkey's Bowls")
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                    }
                    
                    Spacer()
                    
                    HStack(spacing: 4) {
                        Image(systemName: weather.condition.sfSymbol)
                            .font(.caption)
                        Text(weather.formattedTempC)
                            .font(.caption.weight(.bold))
                            .monospacedDigit()
                    }
                    .padding(.horizontal, 8)
                    .padding(.vertical, 4)
                    .background(Capsule().fill(.ultraThinMaterial))
                }
                
                Spacer()
                
                HStack {
                    Text("\(slopes.count) Active Trails")
                        .font(.caption2.weight(.semibold))
                        .foregroundStyle(.secondary)
                    
                    Spacer()
                    
                    Text("\(lifts.count) Lifts Running")
                        .font(.caption2.weight(.semibold))
                        .foregroundStyle(AlpineTheme.glacierBlue)
                }
            }
            .padding(16)
        }
        .frame(height: 200)
        .clipShape(RoundedRectangle(cornerRadius: 22, style: .continuous))
        .overlay(
            RoundedRectangle(cornerRadius: 22, style: .continuous)
                .strokeBorder(Color.white.opacity(0.12), lineWidth: 0.5)
        )
        .shadow(color: Color.black.opacity(0.25), radius: 14, x: 0, y: 7)
    }
}
