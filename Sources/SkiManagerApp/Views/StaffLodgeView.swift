import SwiftUI

public struct StaffLodgeView: View {
    @Environment(ResortStore.self) private var store
    
    @State private var selectedTab: Int = 0 // 0: Staff, 1: Buildings
    @State private var showingHireSheet: Bool = false
    @State private var showingBuildSheet: Bool = false
    
    public init() {}
    
    public var averageMorale: Int {
        guard !store.resort.staff.isEmpty else { return 100 }
        let total = store.resort.staff.reduce(0) { $0 + $1.moralePercent }
        return total / store.resort.staff.count
    }
    
    public var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 20) {
                    // Segment Switcher: Staff vs Commercial Buildings
                    Picker("Management Mode", selection: $selectedTab) {
                        Text("Staff Operations (\(store.resort.staff.count))").tag(0)
                        Text("Lodge & Village (\(store.resort.buildings.count))").tag(1)
                    }
                    .pickerStyle(.segmented)
                    .padding(.horizontal, 16)
                    
                    if selectedTab == 0 {
                        // MARK: - Staff Operations Content
                        VStack(spacing: 16) {
                            // Staff KPI Overview
                            HStack(spacing: 12) {
                                StatPillView(
                                    title: "Staff Headcount",
                                    value: "\(store.resort.staff.count) team",
                                    sfSymbol: "person.3.fill",
                                    tintColor: AlpineTheme.glacierBlue
                                )
                                
                                StatPillView(
                                    title: "Average Morale",
                                    value: "\(averageMorale)%",
                                    sfSymbol: "heart.fill",
                                    tintColor: averageMorale >= 75 ? AlpineTheme.forestGreen : AlpineTheme.peakAmber
                                )
                            }
                            .padding(.horizontal, 16)
                            
                            // Staff Roster Section
                            VStack(alignment: .leading, spacing: 12) {
                                HStack {
                                    Text("Active Employees")
                                        .font(.headline)
                                        .foregroundStyle(.primary)
                                    
                                    Spacer()
                                    
                                    Button {
                                        showingHireSheet = true
                                    } label: {
                                        Label("Hire Staff", systemImage: "person.badge.plus.fill")
                                            .font(.subheadline.weight(.semibold))
                                            .foregroundStyle(AlpineTheme.glacierBlue)
                                    }
                                }
                                .padding(.horizontal, 16)
                                
                                LazyVStack(spacing: 10) {
                                    ForEach(store.resort.staff) { member in
                                        StaffMemberRow(member: member) {
                                            store.fireStaff(id: member.id)
                                        }
                                    }
                                }
                                .padding(.horizontal, 16)
                            }
                        }
                    } else {
                        // MARK: - Buildings & Facilities Content
                        VStack(spacing: 16) {
                            // Buildings Overview
                            HStack(spacing: 12) {
                                StatPillView(
                                    title: "Commercial Lodges",
                                    value: "\(store.resort.buildings.count) sites",
                                    sfSymbol: "building.2.fill",
                                    tintColor: .indigo
                                )
                                
                                StatPillView(
                                    title: "Daily Receipts",
                                    value: "€\(store.resort.dailyLodgeRevenue.formatted())",
                                    sfSymbol: "fork.knife",
                                    tintColor: AlpineTheme.forestGreen
                                )
                            }
                            .padding(.horizontal, 16)
                            
                            // Buildings List
                            VStack(alignment: .leading, spacing: 12) {
                                HStack {
                                    Text("Resort Infrastructure")
                                        .font(.headline)
                                        .foregroundStyle(.primary)
                                    
                                    Spacer()
                                    
                                    Button {
                                        showingBuildSheet = true
                                    } label: {
                                        Label("Add Facility", systemImage: "plus.circle.fill")
                                            .font(.subheadline.weight(.semibold))
                                            .foregroundStyle(AlpineTheme.glacierBlue)
                                    }
                                }
                                .padding(.horizontal, 16)
                                
                                LazyVStack(spacing: 10) {
                                    ForEach(store.resort.buildings) { building in
                                        BuildingRowView(building: building)
                                    }
                                }
                                .padding(.horizontal, 16)
                            }
                        }
                    }
                }
                .padding(.vertical, 16)
            }
            .navigationTitle("Staff & Lodges")
            .navigationBarTitleDisplayMode(.large)
            .sheet(isPresented: $showingHireSheet) {
                HireStaffSheet()
                    .presentationDetents([.medium, .large])
                    .presentationDragIndicator(.visible)
            }
            .sheet(isPresented: $showingBuildSheet) {
                BuildFacilitySheet()
                    .presentationDetents([.medium, .large])
                    .presentationDragIndicator(.visible)
            }
        }
    }
}

// MARK: - Staff Member Row

public struct StaffMemberRow: View {
    public let member: StaffMember
    public let onFire: () -> Void
    
    public var body: some View {
        HStack(spacing: 14) {
            ZStack {
                RoundedRectangle(cornerRadius: 14, style: .continuous)
                    .fill(AlpineTheme.glacierBlue.opacity(0.15))
                    .frame(width: 44, height: 44)
                
                Image(systemName: member.role.sfSymbol)
                    .font(.title3)
                    .foregroundStyle(AlpineTheme.glacierBlue)
            }
            
            VStack(alignment: .leading, spacing: 4) {
                HStack(spacing: 6) {
                    Text(member.name)
                        .font(.headline)
                    
                    Spacer()
                    
                    Text("€\(member.salaryPerDay)/day")
                        .font(.subheadline.weight(.semibold))
                        .foregroundStyle(.secondary)
                        .monospacedDigit()
                }
                
                HStack(spacing: 8) {
                    Text(member.role.rawValue)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    
                    Text("•")
                        .font(.caption2)
                        .foregroundStyle(.tertiary)
                    
                    HStack(spacing: 2) {
                        ForEach(0..<member.skillStars, id: \.self) { _ in
                            Image(systemName: "star.fill")
                                .font(.system(size: 9))
                                .foregroundStyle(AlpineTheme.peakAmber)
                        }
                    }
                    
                    Spacer()
                    
                    HStack(spacing: 4) {
                        Circle()
                            .fill(member.moraleColor)
                            .frame(width: 6, height: 6)
                        Text("\(member.moralePercent)% Morale")
                            .font(.system(size: 11, weight: .medium))
                            .foregroundStyle(.secondary)
                            .monospacedDigit()
                    }
                }
            }
            
            Button(role: .destructive, action: onFire) {
                Image(systemName: "xmark.circle")
                    .font(.title3)
                    .foregroundStyle(.secondary)
                    .frame(width: 44, height: 44)
            }
            .buttonStyle(.plain)
        }
        .padding(14)
        .background {
            RoundedRectangle(cornerRadius: 18, style: .continuous)
                .fill(.ultraThinMaterial)
                .overlay(
                    RoundedRectangle(cornerRadius: 18, style: .continuous)
                        .strokeBorder(Color.white.opacity(0.1), lineWidth: 0.5)
                )
        }
    }
}

// MARK: - Building Row

public struct BuildingRowView: View {
    public let building: ResortBuilding
    
    public var body: some View {
        HStack(spacing: 14) {
            ZStack {
                RoundedRectangle(cornerRadius: 14, style: .continuous)
                    .fill(Color.indigo.opacity(0.15))
                    .frame(width: 44, height: 44)
                
                Image(systemName: building.buildingType.sfSymbol)
                    .font(.title3)
                    .foregroundStyle(.indigo)
            }
            
            VStack(alignment: .leading, spacing: 4) {
                HStack {
                    Text(building.name)
                        .font(.headline)
                        .lineLimit(1)
                    
                    Spacer()
                    
                    Text("Level \(building.upgradeLevel)")
                        .font(.caption.weight(.bold))
                        .foregroundStyle(AlpineTheme.peakAmber)
                        .padding(.horizontal, 8)
                        .padding(.vertical, 2)
                        .background(Capsule().fill(AlpineTheme.peakAmber.opacity(0.15)))
                }
                
                HStack(spacing: 8) {
                    Text(building.buildingType.rawValue)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    
                    Spacer()
                    
                    if building.dailyRevenue > 0 {
                        Text("+€\(building.dailyRevenue.formatted())/day")
                            .font(.caption.weight(.semibold))
                            .foregroundStyle(AlpineTheme.forestGreen)
                            .monospacedDigit()
                    }
                }
            }
        }
        .padding(14)
        .background {
            RoundedRectangle(cornerRadius: 18, style: .continuous)
                .fill(.ultraThinMaterial)
                .overlay(
                    RoundedRectangle(cornerRadius: 18, style: .continuous)
                        .strokeBorder(Color.white.opacity(0.1), lineWidth: 0.5)
                )
        }
    }
}

// MARK: - Hire Staff Sheet

public struct HireStaffSheet: View {
    @Environment(\.dismiss) private var dismiss
    @Environment(ResortStore.self) private var store
    
    @State private var staffName: String = ""
    @State private var selectedRole: StaffRole = .liftOperator
    
    public var body: some View {
        NavigationStack {
            Form {
                Section(header: Text("Employee Details")) {
                    TextField("Full Name (e.g. Erik Lind)", text: $staffName)
                        .autocorrectionDisabled()
                    
                    Picker("Role & Department", selection: $selectedRole) {
                        ForEach(StaffRole.allCases) { role in
                            HStack {
                                Image(systemName: role.sfSymbol)
                                Text(role.rawValue)
                            }
                            .tag(role)
                        }
                    }
                }
                
                Section(header: Text("Role Overview")) {
                    Text(selectedRole.roleDescription)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    
                    HStack {
                        Text("Starting Daily Salary")
                        Spacer()
                        Text("€\(selectedRole.baseSalary)/day")
                            .font(.subheadline.weight(.semibold))
                            .monospacedDigit()
                    }
                }
            }
            .navigationTitle("Hire New Staff")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") { dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Hire") {
                        let finalName = staffName.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
                            ? "\(selectedRole.rawValue) #\(store.resort.staff.count + 1)"
                            : staffName.trimmingCharacters(in: .whitespacesAndNewlines)
                        _ = store.hireStaff(name: finalName, role: selectedRole)
                        dismiss()
                    }
                    .fontWeight(.bold)
                }
            }
        }
    }
}

// MARK: - Build Facility Sheet

public struct BuildFacilitySheet: View {
    @Environment(\.dismiss) private var dismiss
    @Environment(ResortStore.self) private var store
    
    @State private var facilityName: String = ""
    @State private var selectedType: BuildingType = .mountainLodge
    
    public var canAfford: Bool {
        store.resort.cash >= selectedType.baseBuildCost
    }
    
    public var body: some View {
        NavigationStack {
            Form {
                Section(header: Text("Facility Details")) {
                    TextField("Name (e.g. Summit Ridge Cafe)", text: $facilityName)
                    
                    Picker("Facility Type", selection: $selectedType) {
                        ForEach(BuildingType.allCases) { type in
                            HStack {
                                Image(systemName: type.sfSymbol)
                                Text(type.rawValue)
                            }
                            .tag(type)
                        }
                    }
                }
                
                Section(header: Text("Financial Projections")) {
                    HStack {
                        Text("Construction Price")
                        Spacer()
                        Text("€\(selectedType.baseBuildCost.formatted())")
                            .font(.subheadline.weight(.bold))
                            .foregroundStyle(canAfford ? AlpineTheme.glacierBlue : AlpineTheme.avalancheRed)
                            .monospacedDigit()
                    }
                    
                    HStack {
                        Text("Base Daily Revenue")
                        Spacer()
                        Text("+€\(selectedType.baseDailyRevenue.formatted())/day")
                            .font(.subheadline.weight(.semibold))
                            .foregroundStyle(AlpineTheme.forestGreen)
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
                        _ = store.constructBuilding(name: finalName, type: selectedType, sector: 1)
                        dismiss()
                    }
                    .disabled(!canAfford)
                    .fontWeight(.bold)
                }
            }
        }
    }
}
