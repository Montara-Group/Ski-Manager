package net.skimanager.app.model

import com.google.gson.annotations.SerializedName

enum class BuildingType(
    val label: String,
    val iconName: String,
    val baseBuildCost: Long,
    val baseDailyIncome: Int,
    val baseDailyUpkeep: Int
) {
    @SerializedName("lodge")
    MOUNTAIN_LODGE("Panorama Mountain Lodge", "hotel", 180000, 4800, 450),

    @SerializedName("bistro")
    APRES_SKI_BISTRO("Après-Ski Bistro & Grill", "restaurant", 95000, 3100, 280),

    @SerializedName("rental")
    RENTAL_SHOP("Summit Equipment & Rental", "ski", 110000, 3900, 320),

    @SerializedName("clinic")
    FIRST_AID_CLINIC("Alpine First Aid Station", "local_hospital", 130000, 0, 380),

    @SerializedName("parking")
    PARKING_LOT("Base Village Parking", "local_parking", 75000, 1800, 120),

    @SerializedName("snowmakingDepot")
    SNOWMAKING_DEPOT("TechnoAlpin Compressor Facility", "ac_unit", 210000, 0, 520)
}

data class ResortBuilding(
    val id: String = java.util.UUID.randomUUID().toString(),
    val name: String,
    val buildingType: BuildingType,
    var upgradeLevel: Int = 1,
    var isOpen: Boolean = true,
    val sectorNumber: Int = 1
) {
    val dailyRevenue: Int
        get() = if (isOpen) (buildingType.baseDailyIncome * (1.0 + (upgradeLevel - 1) * 0.45)).toInt() else 0

    val dailyUpkeep: Int
        get() = (buildingType.baseDailyUpkeep * (1.0 + (upgradeLevel - 1) * 0.25)).toInt()

    val nextUpgradeCost: Long
        get() = (buildingType.baseBuildCost * 0.55 * upgradeLevel).toLong()
}
