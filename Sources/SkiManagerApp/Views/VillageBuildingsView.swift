import Foundation
import SwiftUI

public struct VillageBuildingsView: View {
    @Environment(ResortStore.self) private var store
    
    @State private var showingBuildSheet: Bool = false
    @State private var selectedCategory: String = "All"
    
    public init() {}
    
    private var totalVillageRevenue: Int {
        store.resort.buildings.reduce(0) { $0 + $1.dailyRevenue }
    }
    
    private var totalVillageUpkeep: Int {
        store.resort.buildings.reduce(0) { $0 + $1.dailyUpkeep }
    }
    
    public var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 16) {
                    // Top Stats Bar
                    ResortStatsBarView()
                        .padding(.horizontal, 16)
                    
                    // Village Telemetry Summary
                    HStack(spacing: 12) {
                        StatPillView(
                            title: "Village Revenue",
                            value: "+€\(totalVillageRevenue.formatted())",
                            sfSymbol: "banknote.fill",
                            tintColor: AlpineTheme.forestGreen,
                            subtitle: "\(store.resort.buildings.filter { $0.isOpen }.count) open facilities"
                        )
                        
                        StatPillView(
                            title: "Daily Upkeep",
                            value: "-€\(totalVillageUpkeep.formatted())",
                            sfSymbol: "wrench.fill",
                            tintColor: AlpineTheme.avalancheRed,
                            subtitle: "Maintenance & utilities"
                        )
                    }
                    .padding(.horizontal, 16)
                    
                    // Facilities List Section
                    VStack(alignment: .leading, spacing: 12) {
                        HStack {
                            Text("Resort Facilities (\(store.resort.buildings.count))")
                                .font(.headline)
                                .foregroundStyle(.primary)
                            
                            Spacer()
                            
                            Button {
                                showingBuildSheet = true
                            } label: {
                                Label("Build Facility", systemImage: "plus.circle.fill")
                                    .font(.subheadline.weight(.semibold))
                                    .foregroundStyle(AlpineTheme.glacierBlue)
                            }
                        }
                        .padding(.horizontal, 16)
                        
                        LazyVStack(spacing: 10) {
                            ForEach(store.resort.buildings) { building in
                                FacilityCardRow(building: building)
                            }
                        }
                        .padding(.horizontal, 16)
                    }
                }
                .padding(.vertical, 12)
            }
            .navigationTitle("Village & Lodges")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        showingBuildSheet = true
                    } label: {
                        Image(systemName: "plus.circle.fill")
                            .foregroundStyle(AlpineTheme.glacierBlue)
                            .font(.title3)
                    }
                }
            }
            .sheet(isPresented: $showingBuildSheet) {
                ConstructFacilitySheet()
                    .presentationDetents([.medium, .large])
                    .presentationDragIndicator(.visible)
            }
        }
    }
}

public struct FacilityCardRow: View {
    @Environment(ResortStore.self) private var store
    public let building: ResortBuilding
    
    public var body: some View {
        VStack(spacing: 10) {
            HStack(spacing: 12) {
                ZStack {
                    RoundedRectangle(cornerRadius: 12, style: .continuous)
                        .fill(AlpineTheme.glacierBlue.opacity(0.18))
                        .frame(width: 44, height: 44)
                    
                    Image(systemName: building.buildingType.sfSymbol)
                        .foregroundStyle(AlpineTheme.glacierBlue)
                        .font(.title3)
                }
                
                VStack(alignment: .leading, spacing: 2) {
                    Text(building.name)
                        .font(.subheadline.weight(.semibold))
                        .foregroundStyle(.primary)
                    
                    HStack(spacing: 6) {
                        Text("Level \(building.upgradeLevel)/5")
                            .font(.caption2.weight(.bold))
                            .foregroundStyle(AlpineTheme.peakAmber)
                        
                        Text("•")
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                        
                        Text("+€\(building.dailyRevenue)/day")
                            .font(.caption2.weight(.semibold))
                            .foregroundStyle(AlpineTheme.forestGreen)
                            .monospacedDigit()
                    }
                }
                
                Spacer()
                
                Button {
                    store.toggleBuildingOpen(id: building.id)
                } label: {
                    Text(building.isOpen ? "Open" : "Closed")
                        .font(.caption.weight(.bold))
                        .padding(.horizontal, 10)
                        .padding(.vertical, 5)
                        .background {
                            Capsule().fill(building.isOpen ? AlpineTheme.forestGreen.opacity(0.2) : AlpineTheme.avalancheRed.opacity(0.2))
                        }
                        .foregroundStyle(building.isOpen ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)
                }
            }
            
            Divider().background(Color.white.opacity(0.1))
            
            HStack {
                Text("Upkeep: -€\(building.dailyUpkeep)/day")
                    .font(.caption2)
                    .foregroundStyle(.secondary)
                
                Spacer()
                
                if building.upgradeLevel < 5 {
                    Button {
                        _ = store.upgradeBuilding(id: building.id)
                    } label: {
                        HStack(spacing: 4) {
                            Image(systemName: "arrow.up.circle.fill")
                            Text("Upgrade (€\(building.nextUpgradeCost.formatted()))")
                        }
                        .font(.caption.weight(.bold))
                        .padding(.horizontal, 10)
                        .padding(.vertical, 5)
                        .background { Capsule().fill(AlpineTheme.peakAmber.opacity(0.2)) }
                        .foregroundStyle(AlpineTheme.peakAmber)
                    }
                    .disabled(store.resort.cash < building.nextUpgradeCost)
                } else {
                    Text("Max Level")
                        .font(.caption2.weight(.bold))
                        .foregroundStyle(.secondary)
                }
            }
        }
        .padding(14)
        .background {
            RoundedRectangle(cornerRadius: 16, style: .continuous)
                .fill(.ultraThinMaterial)
        }
    }
}

public struct ConstructFacilitySheet: View {
    @Environment(ResortStore.self) private var store
    @Environment(\.dismiss) private var dismiss
    
    @State private var facilityName: String = ""
    @State private var selectedType: BuildingType = .mountainLodge
    
    public init() {}
    
    public var body: some View {
        NavigationStack {
            Form {
                Section("Facility Details") {
                    TextField("Facility Name", text: $facilityName)
                    Picker("Building Type", selection: $selectedType) {
                        ForEach(BuildingType.allCases) { type in
                            Text(type.rawValue).tag(type)
                        }
                    }
                }
                
                Section("Financial Projections") {
                    HStack {
                        Text("Construction Cost")
                        Spacer()
                        Text("€\(selectedType.baseBuildCost.formatted())")
                            .fontWeight(.bold)
                            .foregroundStyle(store.resort.cash >= selectedType.baseBuildCost ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)
                            .monospacedDigit()
                    }
                    HStack {
                        Text("Est. Daily Income")
                        Spacer()
                        Text("+€\(selectedType.baseDailyRevenue.formatted())/day")
                            .fontWeight(.semibold)
                            .foregroundStyle(AlpineTheme.forestGreen)
                            .monospacedDigit()
                    }
                    HStack {
                        Text("Daily Upkeep")
                        Spacer()
                        Text("-€\(selectedType.baseDailyUpkeep.formatted())/day")
                            .foregroundStyle(.secondary)
                            .monospacedDigit()
                    }
                }
            }
            .navigationTitle("Construct Facility")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") { dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Build") {
                        let finalName = facilityName.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
                            ? selectedType.rawValue
                            : facilityName.trimmingCharacters(in: .whitespacesAndNewlines)
                        if store.constructBuilding(name: finalName, type: selectedType, sector: 1) {
                            dismiss()
                        }
                    }
                    .disabled(store.resort.cash < selectedType.baseBuildCost)
                    .fontWeight(.bold)
                }
            }
        }
    }
}
