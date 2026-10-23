import SwiftUI

public struct AddSlopeSheet: View {
    @Environment(\.dismiss) private var dismiss
    @Environment(ResortStore.self) private var store
    
    @State private var slopeName: String = ""
    @State private var selectedDifficulty: SlopeDifficulty = .blue
    @State private var selectedSector: Int = 1
    @State private var lengthMeters: Double = 1400
    @State private var verticalDropMeters: Double = 320
    @State private var snowmakingEquipped: Bool = true
    @State private var nightSkiingLighted: Bool = false
    @State private var showingErrorAlert: Bool = false
    
    public init() {}
    
    public var calculatedCost: Int {
        var cost = selectedDifficulty.baseCutCost
        if snowmakingEquipped { cost += 20000 }
        if nightSkiingLighted { cost += 15000 }
        return cost
    }
    
    public var canAfford: Bool {
        store.resort.cash >= calculatedCost
    }
    
    public var steepnessPercent: Double {
        guard lengthMeters > 0 else { return 0.0 }
        return (verticalDropMeters / lengthMeters) * 100.0
    }
    
    public var body: some View {
        NavigationStack {
            Form {
                // Section: Trail Identification
                Section(header: Text("Trail Profile")) {
                    TextField("Slope Name (e.g. Cougar Ridge)", text: $slopeName)
                        .autocorrectionDisabled()
                    
                    Picker("Difficulty Rating", selection: $selectedDifficulty) {
                        ForEach(SlopeDifficulty.allCases) { diff in
                            HStack {
                                Image(systemName: diff.sfSymbol)
                                    .foregroundStyle(diff.color)
                                Text(diff.rawValue)
                            }
                            .tag(diff)
                        }
                    }
                    
                    Picker("Mountain Sector", selection: $selectedSector) {
                        Text("Sector 1 (Base Village)").tag(1)
                        Text("Sector 2 (Advanced Peaks)").tag(2)
                    }
                }
                
                // Section: Topography
                Section(header: Text("Topography & Slope Angle")) {
                    VStack(alignment: .leading, spacing: 6) {
                        HStack {
                            Text("Trail Length")
                            Spacer()
                            Text("\(Int(lengthMeters)) m")
                                .font(.subheadline.weight(.semibold))
                                .monospacedDigit()
                        }
                        Slider(value: $lengthMeters, in: 400...4500, step: 50)
                            .tint(AlpineTheme.glacierBlue)
                    }
                    
                    VStack(alignment: .leading, spacing: 6) {
                        HStack {
                            Text("Vertical Drop")
                            Spacer()
                            Text("\(Int(verticalDropMeters)) m")
                                .font(.subheadline.weight(.semibold))
                                .monospacedDigit()
                        }
                        Slider(value: $verticalDropMeters, in: 60...1100, step: 20)
                            .tint(AlpineTheme.glacierBlue)
                    }
                    
                    HStack {
                        Text("Calculated Incline Grade")
                        Spacer()
                        Text(String(format: "%.1f%% grade", steepnessPercent))
                            .font(.subheadline.weight(.semibold))
                            .foregroundStyle(selectedDifficulty.color)
                            .monospacedDigit()
                    }
                }
                
                // Section: Trail Upgrades
                Section(header: Text("Infrastructure Upgrades")) {
                    Toggle(isOn: $snowmakingEquipped) {
                        HStack(spacing: 8) {
                            Image(systemName: "fan.fill")
                                .foregroundStyle(.cyan)
                            VStack(alignment: .leading) {
                                Text("Automated Snowmaking Lances")
                                Text("+€20,000 installation")
                                    .font(.caption2)
                                    .foregroundStyle(.secondary)
                            }
                        }
                    }
                    
                    Toggle(isOn: $nightSkiingLighted) {
                        HStack(spacing: 8) {
                            Image(systemName: "moon.stars.fill")
                                .foregroundStyle(AlpineTheme.peakAmber)
                            VStack(alignment: .leading) {
                                Text("High-Output LED Floodlighting")
                                Text("+€15,000 installation")
                                    .font(.caption2)
                                    .foregroundStyle(.secondary)
                            }
                        }
                    }
                }
                
                // Section: Financial Expenditure
                Section(header: Text("Trail Grading Budget")) {
                    HStack {
                        Text("Grading & Clearing Cost")
                            .font(.headline)
                        Spacer()
                        Text("€\(calculatedCost.formatted())")
                            .font(.headline.weight(.bold))
                            .foregroundStyle(canAfford ? AlpineTheme.glacierBlue : AlpineTheme.avalancheRed)
                            .monospacedDigit()
                    }
                    
                    HStack {
                        Text("Resort Treasury Balance")
                        Spacer()
                        Text("€\(store.resort.cash.formatted())")
                            .foregroundStyle(.secondary)
                            .monospacedDigit()
                    }
                }
            }
            .navigationTitle("Carve New Trail")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") { dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Carve Slope") {
                        let finalName = slopeName.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
                            ? "\(selectedDifficulty.shortLabel) Run #\(store.resort.slopes.count + 1)"
                            : slopeName.trimmingCharacters(in: .whitespacesAndNewlines)
                        
                        let success = store.cutSlope(
                            name: finalName,
                            difficulty: selectedDifficulty,
                            sector: selectedSector,
                            length: Int(lengthMeters),
                            verticalDrop: Int(verticalDropMeters),
                            snowmaking: snowmakingEquipped,
                            nightSkiing: nightSkiingLighted
                        )
                        if success {
                            dismiss()
                        } else {
                            showingErrorAlert = true
                        }
                    }
                    .disabled(!canAfford)
                    .fontWeight(.bold)
                }
            }
            .alert("Carving Blocked", isPresented: $showingErrorAlert) {
                Button("OK", role: .cancel) {}
            } message: {
                Text("Unable to carve new trail. Please ensure sufficient funds exist in the resort treasury.")
            }
        }
    }
}
