# Ski Manager — Native iOS Client & Mountain Simulation Engine

[![Swift 6.0](https://img.shields.io/badge/Swift-6.0-orange.svg?style=flat-square)](https://swift.org)
[![iOS 17+](https://img.shields.io/badge/iOS-17.0%2B-blue.svg?style=flat-square)](https://developer.apple.com/ios/)
[![Design](https://img.shields.io/badge/HIG-Cupertino%20Alpine%20Glass-cyan.svg?style=flat-square)](https://developer.apple.com/design/human-interface-guidelines/)

An Apple-grade, native iOS simulation game and live operations client for **Ski Manager** (`https://ski-manager.net`). Built from the ground up adhering to Apple’s Human Interface Guidelines (HIG), ProMotion 120Hz spring choreographies, and modern Swift 6 architecture.

---

## Key Highlights

- **Full Hybrid Architecture**: 
  - Play fully offline with zero server dependencies using the local alpine simulation engine.
  - Automatically persists resort progress (cash, lifts, slopes, staff, buildings) to on-device JSON storage.
  - Connects to `https://ski-manager.net` to check live server health, Season 2 maintenance windows, and global pioneer leaderboards.
- **Cupertino Alpine Glass Aesthetic**:
  - Layered `.ultraThinMaterial` frosted glass cards.
  - Dynamic mountain backdrops matching live weather conditions (Bluebird Powder, Sunny, Blizzard).
  - Tactile physical button styles with instant spring down-scale feedback (`AlpineGlassButtonStyle`).
- **Ergonomics & Thumb Zone**:
  - Interactive 5-tab Cupertino shell (`MainTabView`) with authentic SF Symbols and active state variants.
  - Primary day advancement and operations controls anchored in the lower thumb zone.
  - Minimum 44×44pt touch targets on all interactive controls.
- **Deep Mountain Simulation Engine (`ResortStore`)**:
  - Real-time weather generator with 24h snow accumulation and avalanche danger ratings (1–5).
  - Dynamic ticket price elasticity and visitor attendance calculations.
  - Daily income vs expense ledger with automated payroll, loan interest, and lift maintenance wear.
  - Park City Mountain template pre-configured with Sector 1 Base Village and Sector 2 Advanced Peaks.

---

## App Anatomy

```
SkiManager-iOS/
├── Package.swift                    # Swift Package Manager manifest (Open in Xcode)
├── Info.plist                       # iOS Bundle properties, capabilities & orientations
├── .github/
│   └── workflows/
│       └── build-ipa.yml            # Automated CI workflow to compile and package .ipa
├── Sources/
│   └── SkiManagerApp/
│       ├── SkiManagerApp.swift      # @main application entry point
│       ├── Theme/
│       │   └── AlpineTheme.swift    # Colors, fluid spring physics, glass modifiers & styles
│       ├── Models/
│       │   ├── Lift.swift           # Lift technologies (Carpet, Quad, 6-Pack, Gondola, Tram)
│       │   ├── Slope.swift          # Slopes (Green, Blue, Black, Double Black, Terrain Park)
│       │   ├── Weather.swift        # Live weather, base depth, avalanche alerts & 7-day outlook
│       │   ├── StaffMember.swift    # Roles (Patrol, Groomers, Lift Ops, Instructors, Mechanics)
│       │   ├── Building.swift       # Commercial lodges, hotels, dining & pro shops
│       │   ├── GameTransaction.swift# Financial ledger transaction records
│       │   └── Resort.swift         # Master resort model & Park City template
│       ├── Engine/
│       │   └── ResortStore.swift    # @Observable game engine & local JSON persistence
│       ├── Network/
│       │   └── SkiManagerAPIClient.swift # REST client for ski-manager.net
│       ├── Components/
│       │   ├── WeatherHeroCard.swift # Animated frosted weather card
│       │   ├── StatPillView.swift   # Compact telemetry capsule
│       │   ├── LiftRowView.swift    # Interactive lift row with power toggle
│       │   ├── SlopeRowView.swift   # Slope row with grooming bar & status
│       │   └── QuickActionGrid.swift# Thumb-zone buttons (Advance Day, Groom, Snowmake)
│       └── Views/
│           ├── MainTabView.swift    # Cupertino 5-tab root shell
│           ├── MountainView.swift   # Trail map, schematic canvas & sector switcher
│           ├── AddLiftSheet.swift   # Modal sheet to purchase & construct lifts
│           ├── AddSlopeSheet.swift  # Modal sheet to carve & grade ski trails
│           ├── ResortOpsView.swift  # Operations dashboard & facilities status
│           ├── FinancesView.swift   # Budget itemization, ticket pricing & bank loans
│           ├── StaffLodgeView.swift # Employee roster & village commercial buildings
│           └── CloudSyncView.swift  # Live server sync & pioneer leaderboard
```

---

## How to Build & Install

### Option A: Open Directly in Xcode
1. Open Terminal and navigate to the project directory:
   ```bash
   cd /Users/324080/.gemini/antigravity/scratch/SkiManager-iOS
   ```
2. Double-click or open `Package.swift` in Xcode:
   ```bash
   open Package.swift
   ```
3. Select an iOS Simulator (e.g., iPhone 16 Pro) or your physical iPhone and press **Cmd + R** to run.

### Option B: Sideloading IPA via GitHub Actions
1. Push the code to GitHub (`main` branch).
2. The `.github/workflows/build-ipa.yml` action will compile the native Swift executable, apply ad-hoc code signing, and generate `SkiManager-iOS.ipa`.
3. Download the artifact from the GitHub Actions run.
4. Install onto your iPhone using **Sideloadly**, **AltStore**, or **TrollStore**.
