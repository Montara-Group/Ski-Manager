package net.skimanager.app.ui.screens

import android.view.HapticFeedbackConstants
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.rounded.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.skimanager.app.engine.ResortStore
import net.skimanager.app.model.BuildingType
import net.skimanager.app.model.ResortBuilding
import net.skimanager.app.ui.theme.*
import java.text.NumberFormat
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun VillageScreen(
    store: ResortStore,
    modifier: Modifier = Modifier
) {
    val resort by store.resort.collectAsState()
    val view = LocalView.current
    val currencyFormat = NumberFormat.getIntegerInstance(Locale.GERMANY)

    var showConstructDialog by remember { mutableStateOf(false) }

    Scaffold(
        modifier = modifier.fillMaxSize(),
        floatingActionButton = {
            ExtendedFloatingActionButton(
                onClick = {
                    view.performHapticFeedback(HapticFeedbackConstants.CONTEXT_CLICK)
                    showConstructDialog = true
                },
                shape = PillShape,
                containerColor = MaterialTheme.colorScheme.primary,
                contentColor = MaterialTheme.colorScheme.onPrimary,
                icon = { Icon(Icons.Rounded.AddBusiness, contentDescription = null) },
                text = { Text("Construct Facility", fontWeight = FontWeight.Bold) }
            )
        }
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp),
            contentPadding = PaddingValues(top = 16.dp, bottom = 96.dp)
        ) {
            // Village Commercial Summary Header
            item {
                ElevatedCard(
                    modifier = Modifier.fillMaxWidth(),
                    shape = ExpressiveShapes.large,
                    colors = CardDefaults.elevatedCardColors(
                        containerColor = MaterialTheme.colorScheme.surfaceContainer
                    )
                ) {
                    Column(modifier = Modifier.padding(20.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column {
                                Text(
                                    text = "Park City Commercial Village",
                                    style = MaterialTheme.typography.titleLarge,
                                    fontWeight = FontWeight.Bold
                                )
                                Text(
                                    text = "${resort.buildings.size} active facilities generating daily income",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                            Icon(
                                imageVector = Icons.Rounded.HolidayVillage,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.primary,
                                modifier = Modifier.size(32.dp)
                            )
                        }

                        Spacer(modifier = Modifier.height(16.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Column {
                                Text("Total Village Revenue", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                Text(
                                    text = "+€${currencyFormat.format(resort.dailyLodgeRevenue)}/day",
                                    fontFamily = FontFamily.Monospace,
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 15.sp,
                                    color = AlpineGreen
                                )
                            }
                            Column {
                                Text("Total Upkeep", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                Text(
                                    text = "€${currencyFormat.format(resort.dailyBuildingUpkeep)}/day",
                                    fontFamily = FontFamily.Monospace,
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 15.sp,
                                    color = AlpineCoral
                                )
                            }
                        }
                    }
                }
            }

            // Buildings List
            items(resort.buildings, key = { it.id }) { building ->
                BuildingItemCard(
                    building = building,
                    canAffordUpgrade = resort.cash >= building.nextUpgradeCost && building.upgradeLevel < 5,
                    onToggleOpen = {
                        view.performHapticFeedback(HapticFeedbackConstants.CONTEXT_CLICK)
                        store.toggleBuildingOpen(building.id)
                    },
                    onUpgrade = {
                        view.performHapticFeedback(HapticFeedbackConstants.CONFIRM)
                        store.upgradeBuilding(building.id)
                    }
                )
            }
        }
    }

    // Construct Facility Dialog
    if (showConstructDialog) {
        var selectedType by remember { mutableStateOf(BuildingType.MOUNTAIN_LODGE) }
        var facilityName by remember { mutableStateOf("New Lodge") }

        AlertDialog(
            onDismissRequest = { showConstructDialog = false },
            title = { Text("Construct Commercial Facility", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    OutlinedTextField(
                        value = facilityName,
                        onValueChange = { facilityName = it },
                        label = { Text("Facility Name") },
                        modifier = Modifier.fillMaxWidth(),
                        shape = ExpressiveShapes.medium
                    )

                    Text("Select Facility Type:", style = MaterialTheme.typography.labelMedium)

                    BuildingType.entries.forEach { type ->
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            RadioButton(
                                selected = selectedType == type,
                                onClick = {
                                    selectedType = type
                                    facilityName = type.label
                                }
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Column {
                                Text(type.label, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
                                Text(
                                    "Cost: €${currencyFormat.format(type.baseBuildCost)} · Yield: +€${type.baseDailyIncome}/day",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        store.constructBuilding(name = facilityName, type = selectedType)
                        showConstructDialog = false
                    },
                    enabled = resort.cash >= selectedType.baseBuildCost,
                    shape = PillShape
                ) {
                    Text("Construct (€${currencyFormat.format(selectedType.baseBuildCost)})")
                }
            },
            dismissButton = {
                TextButton(onClick = { showConstructDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }
}

@Composable
fun BuildingItemCard(
    building: ResortBuilding,
    canAffordUpgrade: Boolean,
    onToggleOpen: () -> Unit,
    onUpgrade: () -> Unit
) {
    val currencyFormat = NumberFormat.getIntegerInstance(Locale.GERMANY)

    val icon: ImageVector = when (building.buildingType) {
        BuildingType.MOUNTAIN_LODGE -> Icons.Rounded.Hotel
        BuildingType.APRES_SKI_BISTRO -> Icons.Rounded.Restaurant
        BuildingType.RENTAL_SHOP -> Icons.Rounded.DownhillSkiing
        BuildingType.FIRST_AID_CLINIC -> Icons.Rounded.LocalHospital
        BuildingType.PARKING_LOT -> Icons.Rounded.LocalParking
        BuildingType.SNOWMAKING_DEPOT -> Icons.Rounded.AcUnit
    }

    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = ExpressiveShapes.large,
        colors = CardDefaults.elevatedCardColors(
            containerColor = MaterialTheme.colorScheme.surfaceContainer
        )
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Surface(
                        shape = CircleShape,
                        color = MaterialTheme.colorScheme.primaryContainer,
                        modifier = Modifier.size(44.dp)
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Icon(imageVector = icon, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
                        }
                    }

                    Spacer(modifier = Modifier.width(12.dp))

                    Column {
                        Text(
                            text = building.name,
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            repeat(building.upgradeLevel) {
                                Text("⭐", fontSize = 11.sp)
                            }
                            Spacer(modifier = Modifier.width(4.dp))
                            Text(
                                text = "Level ${building.upgradeLevel} / 5",
                                style = MaterialTheme.typography.labelSmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }

                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(if (building.isOpen) "Open" else "Closed", fontSize = 11.sp)
                    Spacer(modifier = Modifier.width(6.dp))
                    Switch(checked = building.isOpen, onCheckedChange = { onToggleOpen() })
                }
            }

            Spacer(modifier = Modifier.height(14.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text(
                        text = "Daily Revenue: +€${currencyFormat.format(building.dailyRevenue)}",
                        fontSize = 13.sp,
                        fontFamily = FontFamily.Monospace,
                        fontWeight = FontWeight.Bold,
                        color = AlpineGreen
                    )
                    Text(
                        text = "Daily Upkeep: €${currencyFormat.format(building.dailyUpkeep)}",
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                if (building.upgradeLevel < 5) {
                    OutlinedButton(
                        onClick = onUpgrade,
                        enabled = canAffordUpgrade,
                        shape = PillShape
                    ) {
                        Text("Upgrade (Lv.${building.upgradeLevel + 1} €${currencyFormat.format(building.nextUpgradeCost)})", fontSize = 11.sp)
                    }
                } else {
                    Surface(shape = PillShape, color = AlpineGold.copy(alpha = 0.2f)) {
                        Text("MAX LEVEL", fontSize = 10.sp, fontWeight = FontWeight.Bold, color = AlpineGold, modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp))
                    }
                }
            }
        }
    }
}
