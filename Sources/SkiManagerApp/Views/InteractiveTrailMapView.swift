import Foundation
import SwiftUI

public struct InteractiveTrailMapView: View {
    @Environment(ResortStore.self) private var store
    
    // Viewport & Zoom State
    @State private var scale: CGFloat = 1.0
    @State private var lastScale: CGFloat = 1.0
    @State private var offset: CGSize = .zero
    @State private var lastOffset: CGSize = .zero
    
    // Selection & Filter State
    @State private var selectedSegmentId: Int? = nil
    @State private var showAllMountainTrails: Bool = false
    @State private var filterMode: String = "All"
    @State private var showSectorBoundary: Bool = true
    
    // Background Image Cache
    @State private var mountainImage: UIImage? = nil
    
    public init() {}
    
    // Filtered segments based on toggle & difficulty
    private var visibleSegments: [RealMapSegment] {
        var list = store.resort.realSegments
        
        // Mode filter: built vs all
        if !showAllMountainTrails {
            list = list.filter { $0.isBuilt }
        }
        
        // Difficulty / Type filter
        switch filterMode {
        case "Lifts":
            return list.filter { $0.isLift }
        case "Slopes":
            return list.filter { $0.isSlope }
        case "Green":
            return list.filter { $0.difficulty?.lowercased() == "green" }
        case "Blue":
            return list.filter { $0.difficulty?.lowercased() == "blue" }
        case "Black":
            return list.filter { $0.difficulty?.lowercased() == "black" || $0.difficulty?.lowercased() == "double_black" }
        default:
            return list
        }
    }
    
    private var selectedSegment: RealMapSegment? {
        guard let id = selectedSegmentId else { return nil }
        return store.resort.realSegments.first(where: { $0.id == id })
    }
    
    public var body: some View {
        VStack(spacing: 0) {
            // Map Top Control Bar
            HStack(spacing: 12) {
                // View Mode Pill Toggle
                Picker("Map Mode", selection: $showAllMountainTrails) {
                    Text("Built Trails").tag(false)
                    Text("All Trails (Build)").tag(true)
                }
                .pickerStyle(.segmented)
                
                // Reset Zoom Button
                Button {
                    withAnimation(AlpineTheme.fluidSpring) {
                        scale = 1.0
                        lastScale = 1.0
                        offset = .zero
                        lastOffset = .zero
                    }
                } label: {
                    Image(systemName: "arrow.counterclockwise")
                        .font(.caption.weight(.bold))
                        .foregroundStyle(AlpineTheme.glacierBlue)
                        .padding(8)
                        .background {
                            Circle().fill(.ultraThinMaterial)
                        }
                }
            }
            .padding(.horizontal, 16)
            .padding(.vertical, 8)
            
            // Filter Scroll Bar
            ScrollView(.horizontal, showsIndicators: false) {
                HStack(spacing: 8) {
                    ForEach(["All", "Lifts", "Slopes", "Green", "Blue", "Black"], id: \.self) { item in
                        Button {
                            withAnimation(AlpineTheme.snappySpring) {
                                filterMode = item
                            }
                        } label: {
                            Text(item)
                                .font(.caption.weight(.semibold))
                                .padding(.horizontal, 10)
                                .padding(.vertical, 5)
                                .background {
                                    if filterMode == item {
                                        Capsule().fill(AlpineTheme.glacierBlue)
                                    } else {
                                        Capsule().fill(.ultraThinMaterial)
                                    }
                                }
                                .foregroundStyle(filterMode == item ? .black : .primary)
                        }
                    }
                }
                .padding(.horizontal, 16)
                .padding(.bottom, 8)
            }
            
            // Interactive Map Viewport with GeometryReader
            GeometryReader { geo in
                let mapAspect: CGFloat = 600.0 / 340.0 // Authentic Leaflet simple CRS bounds (600x340)
                let canvasWidth = geo.size.width
                let canvasHeight = canvasWidth / mapAspect
                
                ZStack {
                    // Container clipped to frame
                    Color.black.opacity(0.85)
                    
                    // Zoomable & Pannable Mountain Canvas
                    ZStack {
                        // 1. Authentic Park City Mountain Map Image
                        if let img = mountainImage {
                            Image(uiImage: img)
                                .resizable()
                                .aspectRatio(contentMode: .fill)
                                .frame(width: canvasWidth, height: canvasHeight)
                                .clipped()
                        } else {
                            // High-end stylized alpine fallback while image loads
                            TopographicMountainBackground(width: canvasWidth, height: canvasHeight)
                        }
                        
                        // 2. Leaflet Coordinate Segments & Sectors Overlay
                        Canvas { context, size in
                            let w = size.width
                            let h = size.height
                            
                            // Render Sector 1 Boundary if available
                            if showSectorBoundary, let sector = store.resort.realSectors.first, !sector.boundaryPoints.isEmpty {
                                var sectorPath = Path()
                                let first = sector.boundaryPoints[0]
                                let startX = (first[1] / 600.0) * w
                                let startY = ((340.0 - first[0]) / 340.0) * h
                                sectorPath.move(to: CGPoint(x: startX, y: startY))
                                
                                for pt in sector.boundaryPoints.dropFirst() {
                                    let px = (pt[1] / 600.0) * w
                                    let py = ((340.0 - pt[0]) / 340.0) * h
                                    sectorPath.addLine(to: CGPoint(x: px, y: py))
                                }
                                sectorPath.closeSubpath()
                                
                                // Subtle sector fill & border
                                context.fill(sectorPath, with: .color(Color(red: 0.23, green: 0.51, blue: 0.96).opacity(0.08)))
                                context.stroke(
                                    sectorPath,
                                    with: .color(Color(red: 0.23, green: 0.51, blue: 0.96).opacity(0.6)),
                                    style: StrokeStyle(lineWidth: 1.5, dash: [4, 4])
                                )
                            }
                            
                            // Render All Visible Map Segments
                            for seg in visibleSegments {
                                guard seg.points.count >= 2 else { continue }
                                
                                var path = Path()
                                let first = seg.points[0]
                                let sx = (first[1] / 600.0) * w
                                let sy = ((340.0 - first[0]) / 340.0) * h
                                path.move(to: CGPoint(x: sx, y: sy))
                                
                                for pt in seg.points.dropFirst() {
                                    let px = (pt[1] / 600.0) * w
                                    let py = ((340.0 - pt[0]) / 340.0) * h
                                    path.addLine(to: CGPoint(x: px, y: py))
                                }
                                
                                let isSelected = (seg.id == selectedSegmentId)
                                
                                // Stroke styling matching website
                                if seg.isBuilt {
                                    // Drop shadow for high-altitude clarity
                                    context.stroke(
                                        path,
                                        with: .color(.black.opacity(0.4)),
                                        style: StrokeStyle(lineWidth: isSelected ? 8.0 : 5.0, lineCap: .round, lineJoin: .round)
                                    )
                                    // Solid vibrant line
                                    context.stroke(
                                        path,
                                        with: .color(seg.strokeColor),
                                        style: StrokeStyle(lineWidth: isSelected ? 6.5 : 3.8, lineCap: .round, lineJoin: .round)
                                    )
                                } else {
                                    // Unbuilt trail dashed purple/slate line
                                    context.stroke(
                                        path,
                                        with: .color(Color(red: 0.66, green: 0.33, blue: 0.97).opacity(0.9)),
                                        style: StrokeStyle(lineWidth: isSelected ? 6.0 : 3.0, lineCap: .round, lineJoin: .round, dash: [6, 4])
                                    )
                                }
                                
                                // Lift terminus terminal stations
                                if seg.isLift {
                                    let topPt = seg.points.last ?? first
                                    let ex = (topPt[1] / 600.0) * w
                                    let ey = ((340.0 - topPt[0]) / 340.0) * h
                                    
                                    let baseCircle = Path(ellipseIn: CGRect(x: sx - 4, y: sy - 4, width: 8, height: 8))
                                    let topCircle = Path(ellipseIn: CGRect(x: ex - 4, y: ey - 4, width: 8, height: 8))
                                    
                                    context.fill(baseCircle, with: .color(seg.strokeColor))
                                    context.fill(topCircle, with: .color(seg.strokeColor))
                                    context.stroke(baseCircle, with: .color(.white), lineWidth: 1.2)
                                    context.stroke(topCircle, with: .color(.white), lineWidth: 1.2)
                                }
                                
                                // Selection halo
                                if isSelected {
                                    context.stroke(
                                        path,
                                        with: .color(.white.opacity(0.85)),
                                        style: StrokeStyle(lineWidth: 1.8, lineCap: .round, lineJoin: .round)
                                    )
                                }
                            }
                        }
                        .frame(width: canvasWidth, height: canvasHeight)
                    }
                    .frame(width: canvasWidth, height: canvasHeight)
                    .scaleEffect(scale)
                    .offset(offset)
                    .gesture(
                        SimultaneousGesture(
                            MagnificationGesture()
                                .onChanged { value in
                                    let delta = value / lastScale
                                    lastScale = value
                                    scale = max(1.0, min(4.5, scale * delta))
                                }
                                .onEnded { _ in
                                    lastScale = 1.0
                                    if scale <= 1.0 {
                                        withAnimation(AlpineTheme.snappySpring) {
                                            offset = .zero
                                            lastOffset = .zero
                                        }
                                    }
                                },
                            DragGesture()
                                .onChanged { value in
                                    if scale > 1.0 {
                                        offset = CGSize(
                                            width: lastOffset.width + value.translation.width,
                                            height: lastOffset.height + value.translation.height
                                        )
                                    }
                                }
                                .onEnded { _ in
                                    lastOffset = offset
                                }
                        )
                    )
                    .onTapGesture { location in
                        // Convert tap position to Leaflet coordinates and find closest segment
                        handleMapTap(at: location, canvasWidth: canvasWidth, canvasHeight: canvasHeight)
                    }
                    
                    // Selected Trail Inspector Sheet (Floating Ergonomic Card)
                    if let seg = selectedSegment {
                        VStack {
                            Spacer()
                            SelectedSegmentCard(segment: seg) {
                                withAnimation(AlpineTheme.snappySpring) {
                                    selectedSegmentId = nil
                                }
                            }
                            .padding(.horizontal, 16)
                            .padding(.bottom, 12)
                            .transition(.move(edge: .bottom).combined(with: .opacity))
                        }
                    }
                }
                .frame(width: geo.size.width, height: geo.size.height)
                .clipped()
            }
            .frame(minHeight: 280)
        }
        .onAppear {
            loadMapImage()
        }
    }
    
    // MARK: - Tap to Select Segment
    
    private func handleMapTap(at location: CGPoint, canvasWidth: CGFloat, canvasHeight: CGFloat) {
        // Reverse affine coordinate projection:
        // x = (lng / 600.0) * canvasWidth  => lng = (x / canvasWidth) * 600.0
        // y = ((340.0 - lat) / 340.0) * canvasHeight => lat = 340.0 - (y / canvasHeight) * 340.0
        let tapLng = Double(location.x / canvasWidth) * 600.0
        let tapLat = 340.0 - Double(location.y / canvasHeight) * 340.0
        
        var closestId: Int? = nil
        var minDistance: Double = 18.0 // Hit tolerance threshold in Leaflet coordinate units
        
        for seg in visibleSegments {
            for pt in seg.points {
                let dLat = pt[0] - tapLat
                let dLng = pt[1] - tapLng
                let dist = sqrt(dLat * dLat + dLng * dLng)
                if dist < minDistance {
                    minDistance = dist
                    closestId = seg.id
                }
            }
        }
        
        withAnimation(AlpineTheme.snappySpring) {
            selectedSegmentId = closestId
        }
    }
    
    // MARK: - Image Loader
    
    private func loadMapImage() {
        // 1. Try bundled asset
        if let path = Bundle.main.path(forResource: "ParkCity", ofType: "jpg"),
           let img = UIImage(contentsOfFile: path) {
            self.mountainImage = img
            return
        }
        
        // 2. Try Resources folder
        let possiblePaths = [
            "ParkCity.jpg",
            "Resources/ParkCity.jpg",
            "Sources/SkiManagerApp/Resources/ParkCity.jpg"
        ]
        for p in possiblePaths {
            if FileManager.default.fileExists(atPath: p),
               let img = UIImage(contentsOfFile: p) {
                self.mountainImage = img
                return
            }
        }
        
        // 3. Fallback: load asynchronously from ski-manager.net
        Task {
            guard let url = URL(string: "https://ski-manager.net/img/ParkCity_med.jpg") else { return }
            if let (data, _) = try? await URLSession.shared.data(from: url),
               let downloaded = UIImage(data: data) {
                await MainActor.run {
                    self.mountainImage = downloaded
                }
            }
        }
    }
}

// MARK: - Selected Segment Floating Inspector Card

public struct SelectedSegmentCard: View {
    @Environment(ResortStore.self) private var store
    public let segment: RealMapSegment
    public let onDismiss: () -> Void
    
    public var body: some View {
        VStack(spacing: 12) {
            HStack(spacing: 12) {
                ZStack {
                    RoundedRectangle(cornerRadius: 12, style: .continuous)
                        .fill(segment.strokeColor.opacity(0.2))
                        .frame(width: 44, height: 44)
                    
                    Image(systemName: segment.sfSymbol)
                        .font(.title3)
                        .foregroundStyle(segment.strokeColor)
                }
                
                VStack(alignment: .leading, spacing: 2) {
                    Text(segment.name)
                        .font(.headline)
                        .foregroundStyle(.primary)
                        .lineLimit(1)
                    
                    HStack(spacing: 6) {
                        Text(segment.difficultyDisplay)
                            .font(.caption2.weight(.bold))
                            .foregroundStyle(segment.strokeColor)
                        
                        Text("•")
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                        
                        Text("\(segment.lengthMeters) m (\(String(format: "%.1f", Double(segment.lengthMeters) / 1000.0)) km)")
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                            .monospacedDigit()
                    }
                }
                
                Spacer()
                
                Button(action: onDismiss) {
                    Image(systemName: "xmark.circle.fill")
                        .font(.title3)
                        .foregroundStyle(.secondary)
                }
            }
            
            Divider()
                .background(Color.white.opacity(0.15))
            
            // Actions Row
            if segment.isBuilt {
                HStack(spacing: 12) {
                    // Operational Status Indicator
                    HStack(spacing: 6) {
                        Circle()
                            .fill(segment.isOpen ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)
                            .frame(width: 8, height: 8)
                        Text(segment.isOpen ? "Open for Skiers" : "Closed")
                            .font(.caption.weight(.semibold))
                    }
                    
                    Spacer()
                    
                    Button {
                        store.toggleRealSegmentOpen(id: segment.id)
                    } label: {
                        Text(segment.isOpen ? "Close Trail" : "Open Trail")
                            .font(.subheadline.weight(.semibold))
                            .padding(.horizontal, 14)
                            .padding(.vertical, 8)
                            .background {
                                Capsule().fill(segment.isOpen ? AlpineTheme.avalancheRed.opacity(0.25) : AlpineTheme.forestGreen.opacity(0.25))
                            }
                            .foregroundStyle(segment.isOpen ? AlpineTheme.avalancheRed : AlpineTheme.forestGreen)
                    }
                }
            } else {
                HStack(spacing: 12) {
                    VStack(alignment: .leading, spacing: 2) {
                        Text("Construction Cost")
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                        Text("€\(segment.constructionCost.formatted())")
                            .font(.subheadline.weight(.bold))
                            .foregroundStyle(store.resort.cash >= segment.constructionCost ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)
                            .monospacedDigit()
                    }
                    
                    Spacer()
                    
                    Button {
                        _ = store.buildRealSegment(id: segment.id)
                    } label: {
                        Label(
                            store.resort.cash >= segment.constructionCost ? (segment.isLift ? "Build Lift" : "Clear Trail") : "Insufficient Funds",
                            systemImage: "hammer.fill"
                        )
                        .font(.subheadline.weight(.semibold))
                        .padding(.horizontal, 16)
                        .padding(.vertical, 8)
                        .background {
                            Capsule().fill(store.resort.cash >= segment.constructionCost ? AlpineTheme.glacierBlue : Color.gray.opacity(0.3))
                        }
                        .foregroundStyle(store.resort.cash >= segment.constructionCost ? .black : .secondary)
                    }
                    .disabled(store.resort.cash < segment.constructionCost)
                }
            }
        }
        .padding(14)
        .background {
            RoundedRectangle(cornerRadius: 18, style: .continuous)
                .fill(.ultraThinMaterial)
                .shadow(color: .black.opacity(0.4), radius: 16, y: 6)
        }
        .overlay {
            RoundedRectangle(cornerRadius: 18, style: .continuous)
                .stroke(Color.white.opacity(0.2), lineWidth: 1)
        }
    }
}

// MARK: - Topographic Mountain Canvas Fallback

public struct TopographicMountainBackground: View {
    public let width: CGFloat
    public let height: CGFloat
    
    public var body: some View {
        Canvas { context, size in
            let w = size.width
            let h = size.height
            
            // Base dark mountain gradient
            let bgRect = CGRect(origin: .zero, size: size)
            context.fill(
                Path(bgRect),
                with: .linearGradient(
                    Gradient(colors: [
                        Color(red: 0.12, green: 0.18, blue: 0.28),
                        Color(red: 0.06, green: 0.09, blue: 0.16)
                    ]),
                    startPoint: .zero,
                    endPoint: CGPoint(x: w, y: h)
                )
            )
            
            // Stylized topographic ridge contour lines
            for i in 1...6 {
                let frac = CGFloat(i) / 7.0
                var ridge = Path()
                ridge.move(to: CGPoint(x: 0, y: h * (0.3 + frac * 0.6)))
                ridge.addCurve(
                    to: CGPoint(x: w * 0.5, y: h * (0.15 + frac * 0.5)),
                    control1: CGPoint(x: w * 0.2, y: h * (0.25 + frac * 0.4)),
                    control2: CGPoint(x: w * 0.35, y: h * (0.1 + frac * 0.5))
                )
                ridge.addCurve(
                    to: CGPoint(x: w, y: h * (0.4 + frac * 0.5)),
                    control1: CGPoint(x: w * 0.7, y: h * (0.2 + frac * 0.5)),
                    control2: CGPoint(x: w * 0.85, y: h * (0.35 + frac * 0.5))
                )
                
                context.stroke(
                    ridge,
                    with: .color(Color.white.opacity(0.08)),
                    lineWidth: 1.0
                )
            }
        }
        .frame(width: width, height: height)
    }
}
