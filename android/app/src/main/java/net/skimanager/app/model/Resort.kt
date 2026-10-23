package net.skimanager.app.model

import com.google.gson.annotations.SerializedName
import java.util.UUID

enum class ResortDifficulty(
    val label: String,
    val startingCash: Long,
    val visitorMultiplier: Double
) {
    @SerializedName("casual")
    CASUAL("Scenic Alpine (Casual)", 400000L, 1.35),

    @SerializedName("standard")
    STANDARD("Resort Manager (Standard)", 250000L, 1.0),

    @SerializedName("expert")
    EXPERT("Mountain Hardcore (Expert)", 120000L, 0.75)
}

data class Resort(
    val id: String = UUID.randomUUID().toString(),
    var name: String = "Park City Mountain Resort",
    var directorUsername: String = "Director Alex",
    var mapName: String = "Park City",
    var difficulty: ResortDifficulty = ResortDifficulty.STANDARD,
    var seasonNumber: Int = 1,
    var gameDay: Int = 42,
    var totalSeasonDays: Int = 135,
    var winterSeasonDays: Int = 100,
    var cash: Long = 250000L,
    var genepis: Int = 10,
    var reputationScore: Int = 85,
    var ticketPriceAdult: Int = 68,
    var ticketPriceChild: Int = 38,
    var totalVisitorsToday: Int = 480,
    var outstandingLoan: Long = 0L,

    // Daily Bonus & Streak
    var dailyBonusStreak: Int = 1,
    var lastBonusClaimDay: Int = 0,

    // Equipment Condition
    var groomerConditionPct: Int = 95,
    var snowmakingGunsCount: Int = 12,

    // Domain Entities
    var lifts: MutableList<Lift> = mutableListOf(),
    var slopes: MutableList<Slope> = mutableListOf(),
    var staff: MutableList<StaffMember> = mutableListOf(),
    var buildings: MutableList<ResortBuilding> = mutableListOf(),
    var weather: MountainWeather = MountainWeather(),
    var transactions: MutableList<GameTransaction> = mutableListOf(),

    // Authentic Leaflet Segments & Sectors
    var realSegments: MutableList<RealMapSegment> = mutableListOf(),
    var realSectors: MutableList<RealMapSector> = mutableListOf(),
    var unlockedSectors: MutableList<Int> = mutableListOf(1)
) {
    // Financial & Operational Metrics
    val dailyTicketRevenue: Long
        get() {
            val adultRev = (totalVisitorsToday * 0.72) * ticketPriceAdult
            val childRev = (totalVisitorsToday * 0.28) * ticketPriceChild
            return (adultRev + childRev).toLong()
        }

    val dailyLodgeRevenue: Long
        get() = buildings.filter { it.isOpen }.sumOf { it.dailyRevenue.toLong() }

    val totalEstimatedDailyRevenue: Long
        get() = dailyTicketRevenue + dailyLodgeRevenue

    val dailyStaffExpenses: Long
        get() = staff.sumOf { it.salaryPerDay.toLong() }

    val dailyLiftMaintenance: Long
        get() {
            val realLiftCost = realSegments.filter { it.isLift && it.isBuilt && it.isOpen }.size * 180L
            val customLiftCost = lifts.filter { it.status == LiftStatus.RUNNING }.sumOf { it.dailyMaintenanceCost.toLong() }
            return maxOf(realLiftCost, customLiftCost)
        }

    val dailySlopeUpkeep: Long
        get() {
            val realSlopeCost = realSegments.filter { it.isSlope && it.isBuilt && it.isOpen }.size * 85L
            val customSlopeCost = slopes.filter { it.isOpen }.sumOf { it.dailyUpkeepCost.toLong() }
            return maxOf(realSlopeCost, customSlopeCost)
        }

    val dailyBuildingUpkeep: Long
        get() = buildings.sumOf { it.dailyUpkeep.toLong() }

    val dailyLoanInterest: Long
        get() = (outstandingLoan * 0.003).toLong()

    val totalEstimatedDailyExpenses: Long
        get() = dailyStaffExpenses + dailyLiftMaintenance + dailySlopeUpkeep + dailyBuildingUpkeep + dailyLoanInterest

    val netDailyProfit: Long
        get() = totalEstimatedDailyRevenue - totalEstimatedDailyExpenses

    val totalMountainCapacityPerHour: Int
        get() {
            val realCap = realSegments.filter { it.isLift && it.isBuilt && it.isOpen }.sumOf { it.capacity }
            val customCap = lifts.filter { it.status == LiftStatus.RUNNING }.sumOf { it.hourlyCapacity }
            return maxOf(realCap, customCap)
        }

    val totalSkiableLengthKm: Double
        get() {
            val realM = realSegments.filter { it.isSlope && it.isBuilt }.sumOf { it.lengthMeters }
            val customM = slopes.sumOf { it.lengthMeters }
            return maxOf(realM, customM) / 1000.0
        }

    val openSlopesCount: Int
        get() {
            val realOpen = realSegments.filter { it.isSlope && it.isBuilt && it.isOpen }.size
            val customOpen = slopes.filter { it.isOpen }.size
            return maxOf(realOpen, customOpen)
        }

    val runningLiftsCount: Int
        get() {
            val realRunning = realSegments.filter { it.isLift && it.isBuilt && it.isOpen }.size
            val customRunning = lifts.filter { it.status == LiftStatus.RUNNING }.size
            return maxOf(realRunning, customRunning)
        }

    val canClaimDailyBonus: Boolean
        get() = lastBonusClaimDay < gameDay

    val seasonProgressRatio: Float
        get() = (gameDay.coerceAtMost(totalSeasonDays).toFloat() / totalSeasonDays.toFloat())

    companion object {
        fun makeParkCityTemplate(): Resort {
            val defaultLifts = mutableListOf(
                Lift(name = "First Time Quad", liftType = LiftType.FIXED_QUAD, sectorNumber = 1, lengthMeters = 650, verticalRiseMeters = 140, status = LiftStatus.RUNNING, conditionPercent = 100),
                Lift(name = "Payday Express", liftType = LiftType.EXPRESS_QUAD, sectorNumber = 1, lengthMeters = 1400, verticalRiseMeters = 380, status = LiftStatus.RUNNING, conditionPercent = 100),
                Lift(name = "Bonanza High-Speed 6", liftType = LiftType.SIX_PACK_EXPRESS, sectorNumber = 1, lengthMeters = 1850, verticalRiseMeters = 490, status = LiftStatus.RUNNING, conditionPercent = 100),
                Lift(name = "Town Lift Triple", liftType = LiftType.FIXED_DOUBLE, sectorNumber = 1, lengthMeters = 1100, verticalRiseMeters = 290, status = LiftStatus.RUNNING, conditionPercent = 100)
            )

            val defaultSlopes = mutableListOf(
                Slope(name = "First Time Cruiser", difficulty = SlopeDifficulty.GREEN, sectorNumber = 1, lengthMeters = 650, verticalDropMeters = 80, groomingPercent = 100, snowmakingEquipped = true, nightSkiingLighted = true, isOpen = true, snowDepthCm = 142, snowQuality = SnowQuality.GROOMED_CORDUROY),
                Slope(name = "Payday Run", difficulty = SlopeDifficulty.BLUE, sectorNumber = 1, lengthMeters = 1400, verticalDropMeters = 380, groomingPercent = 90, snowmakingEquipped = true, nightSkiingLighted = true, isOpen = true, snowDepthCm = 142, snowQuality = SnowQuality.GROOMED_CORDUROY),
                Slope(name = "Crescent Ridge", difficulty = SlopeDifficulty.BLUE, sectorNumber = 1, lengthMeters = 1200, verticalDropMeters = 320, groomingPercent = 85, snowmakingEquipped = true, nightSkiingLighted = false, isOpen = true, snowDepthCm = 142, snowQuality = SnowQuality.PACKED_POWDER),
                Slope(name = "Lost Prospector", difficulty = SlopeDifficulty.GREEN, sectorNumber = 1, lengthMeters = 900, verticalDropMeters = 150, groomingPercent = 90, snowmakingEquipped = true, nightSkiingLighted = false, isOpen = true, snowDepthCm = 142, snowQuality = SnowQuality.GROOMED_CORDUROY),
                Slope(name = "Silver King Chute", difficulty = SlopeDifficulty.BLACK, sectorNumber = 1, lengthMeters = 1100, verticalDropMeters = 410, groomingPercent = 40, snowmakingEquipped = false, nightSkiingLighted = false, isOpen = true, snowDepthCm = 142, snowQuality = SnowQuality.DEEP_POWDER),
                Slope(name = "Thaynes Canyon", difficulty = SlopeDifficulty.BLACK, sectorNumber = 1, lengthMeters = 1300, verticalDropMeters = 460, groomingPercent = 30, snowmakingEquipped = false, nightSkiingLighted = false, isOpen = true, snowDepthCm = 142, snowQuality = SnowQuality.DEEP_POWDER),
                Slope(name = "3 Kings Park", difficulty = SlopeDifficulty.TERRAIN_PARK, sectorNumber = 1, lengthMeters = 850, verticalDropMeters = 180, groomingPercent = 95, snowmakingEquipped = true, nightSkiingLighted = true, isOpen = true, snowDepthCm = 142, snowQuality = SnowQuality.GROOMED_CORDUROY),
                Slope(name = "Quit'N Time", difficulty = SlopeDifficulty.BLUE, sectorNumber = 1, lengthMeters = 750, verticalDropMeters = 190, groomingPercent = 80, snowmakingEquipped = true, nightSkiingLighted = false, isOpen = true, snowDepthCm = 142, snowQuality = SnowQuality.PACKED_POWDER)
            )

            val defaultStaff = mutableListOf(
                StaffMember(name = "Sarah Lindgren", role = StaffRole.SKI_PATROL, salaryPerDay = 150, skillStars = 4, moralePercent = 100),
                StaffMember(name = "Marc Dubois", role = StaffRole.GROOMER, salaryPerDay = 110, skillStars = 4, moralePercent = 100),
                StaffMember(name = "Otto Huber", role = StaffRole.MECHANIC, salaryPerDay = 130, skillStars = 5, moralePercent = 100),
                StaffMember(name = "Elena Rossi", role = StaffRole.INSTRUCTOR, salaryPerDay = 95, skillStars = 3, moralePercent = 100)
            )

            val defaultBuildings = mutableListOf(
                ResortBuilding(name = "Legacy Snow Lodge", buildingType = BuildingType.MOUNTAIN_LODGE, upgradeLevel = 1, isOpen = true, sectorNumber = 1),
                ResortBuilding(name = "Summit Sports Rental", buildingType = BuildingType.RENTAL_SHOP, upgradeLevel = 1, isOpen = true, sectorNumber = 1),
                ResortBuilding(name = "Base Camp Bistro", buildingType = BuildingType.APRES_SKI_BISTRO, upgradeLevel = 1, isOpen = true, sectorNumber = 1)
            )

            val initialTransactions = mutableListOf(
                GameTransaction(title = "Lift Ticket Sales (Game Day 41)", amount = 32640L, category = TransactionCategory.TICKET_SALES, gameDay = 41),
                GameTransaction(title = "Legacy Lodge & Rental Receipts", amount = 4800L, category = TransactionCategory.BUILDING_REVENUE, gameDay = 41),
                GameTransaction(title = "Staff Payroll Deductions", amount = -485L, category = TransactionCategory.STAFF_WAGES, gameDay = 41),
                GameTransaction(title = "Lift Fleet Electricity & Maintenance", amount = -720L, category = TransactionCategory.LIFT_MAINTENANCE, gameDay = 41),
                GameTransaction(title = "PistenBully Groomer Operations", amount = -340L, category = TransactionCategory.SLOPE_MAINTENANCE, gameDay = 41)
            )

            return Resort(
                name = "Park City Mountain Resort",
                directorUsername = "Director Alex",
                mapName = "Park City",
                difficulty = ResortDifficulty.STANDARD,
                seasonNumber = 1,
                gameDay = 42,
                totalSeasonDays = 135,
                winterSeasonDays = 100,
                cash = 250000L,
                genepis = 10,
                reputationScore = 85,
                ticketPriceAdult = 68,
                ticketPriceChild = 38,
                totalVisitorsToday = 480,
                outstandingLoan = 0L,
                dailyBonusStreak = 1,
                lastBonusClaimDay = 0,
                groomerConditionPct = 95,
                snowmakingGunsCount = 12,
                lifts = defaultLifts,
                slopes = defaultSlopes,
                staff = defaultStaff,
                buildings = defaultBuildings,
                weather = MountainWeather(
                    condition = WeatherCondition.BLUEBIRD_POWDER,
                    temperatureCelsius = -4.0,
                    newSnowfall24hCm = 15,
                    baseDepthCm = 142,
                    windSpeedKmh = 14,
                    visibilityKm = 15.0,
                    avalancheDangerLevel = 2,
                    isSnowmakingOptimal = true
                ),
                transactions = initialTransactions,
                realSegments = mutableListOf(),
                realSectors = mutableListOf(),
                unlockedSectors = mutableListOf(1)
            )
        }
    }
}
