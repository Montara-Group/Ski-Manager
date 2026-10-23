import SwiftUI

public struct AddLiftSheet: View {
    @Environment(\.dismiss) private var dismiss
    @Environment(ResortStore.self) private var store
    
    @State private var liftName: String = ""
    @State private var selectedType: LiftType = .expressQuad
    @State private var selectedSector: Int = 1
    @State private var lengthMeters: Double = 1200
    @State private var verticalRiseMeters: Double = 350
    @State private var showingErrorAlert: Bool = false
    
    public init() {}
    
    public var calculatedCost: Int {
        selectedType.baseConstructionCost
    }
    
    public var canAfford: Bool {
        store.resort.cash >= calculatedCost
    }
    
    public var body: some View {
        NavigationStack {
            Form {
                // Section: Lift Type & Name
                Section(header: Text("Lift Specifications")) {
                    TextField("Lift Name (e.g. Eagle Peak Express)", text: $liftName)
                        .autocorrectionDisabled()
                    
                    Picker("Lift Technology", selection: $selectedType) {
                        ForEach(LiftType.allCases) { type in
                            HStack {
                                Image(systemName: type.sfSymbol)
                                Text(type.rawValue)
                            }
                            .tag(type)
                        }
                    }
                    
                    Picker("Mountain Sector", selection: $selectedSector) {
                        Text("Sector 1 (Base Village)").tag(1)
                        Text("Sector 2 (Advanced Peaks)").tag(2)
                    }
                }
                
                // Section: Route Geometry
                Section(header: Text("Route Geometry")) {
                    VStack(alignment: .leading, spacing: 6) {
                        HStack {
                            Text("Total Cable Length")
                            Spacer()
                            Text("\(Int(lengthMeters)) m")
                                .font(.subheadline.weight(.semibold))
                                .monospacedDigit()
                        }
                        Slider(value: $lengthMeters, in: 300...3500, step: 50)
                            .tint(AlpineTheme.glacierBlue)
                    }
                    
                    VStack(alignment: .leading, spacing: 6) {
                        HStack {
                            Text("Vertical Elevation Rise")
                            Spacer()
                            Text("\(Int(verticalRiseMeters)) m")
                                .font(.subheadline.weight(.semibold))
                                .monospacedDigit()
                        }
                        Slider(value: $verticalRiseMeters, in: 50...1000, step: 25)
                            .tint(AlpineTheme.glacierBlue)
                    }
                }
                
                // Section: Operational Performance Telemetry
                Section(header: Text("Operational Estimates")) {
                    HStack {
                        Text("Uphill Capacity")
                        Spacer()
                        Text("\(selectedType.baseCapacityPerHour) skiers/h")
                            .font(.subheadline.weight(.semibold))
                            .monospacedDigit()
                    }
                    
                    HStack {
                        Text("Ride Duration")
                        Spacer()
                        let rideTime = (lengthMeters / selectedType.speedMetersPerSecond) / 60.0
                        Text(String(format: "%.1f minutes", rideTime))
                            .font(.subheadline.weight(.semibold))
                            .monospacedDigit()
                    }
                    
                    HStack {
                        Text("Daily Maintenance Cost")
                        Spacer()
                        Text("€\(selectedType.baseDailyMaintenance)/day")
                            .font(.subheadline.weight(.semibold))
                            .monospacedDigit()
                            .foregroundStyle(.secondary)
                    }
                }
                
                // Section: Capital Investment
                Section(header: Text("Capital Expenditure")) {
                    HStack {
                        Text("Construction Price")
                            .font(.headline)
                        Spacer()
                        Text("€\(calculatedCost.formatted())")
                            .font(.headline.weight(.bold))
                            .foregroundStyle(canAfford ? AlpineTheme.glacierBlue : AlpineTheme.avalancheRed)
                            .monospacedDigit()
                    }
                    
                    HStack {
                        Text("Available Cash")
                        Spacer()
                        Text("€\(store.resort.cash.formatted())")
                            .foregroundStyle(.secondary)
                            .monospacedDigit()
                    }
                    
                    if !canAfford {
                        Text("Insufficient capital in resort treasury. Consider securing a bank loan or saving ticket receipts.")
                            .font(.caption)
                            .foregroundStyle(AlpineTheme.avalancheRed)
                    }
                }
            }
            .navigationTitle("Construct New Lift")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") { dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Build Lift") {
                        let finalName = liftName.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
                            ? "\(selectedType.rawValue) #\(store.resort.lifts.count + 1)"
                            : liftName.trimmingCharacters(in: .whitespacesAndNewlines)
                        
                        let success = store.buildLift(
                            name: finalName,
                            type: selectedType,
                            sector: selectedSector,
                            length: Int(lengthMeters),
                            rise: Int(verticalRiseMeters)
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
            .alert("Construction Blocked", isPresented: $showingErrorAlert) {
                Button("OK", role: .cancel) {}
            } message: {
                Text("Unable to construct lift. Please verify that your resort has sufficient cash available.")
            }
        }
    }
}
