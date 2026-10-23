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
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.skimanager.app.engine.ResortStore
import net.skimanager.app.model.StaffMember
import net.skimanager.app.model.StaffRole
import net.skimanager.app.ui.theme.*
import java.text.NumberFormat
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OperationsScreen(
    store: ResortStore,
    modifier: Modifier = Modifier
) {
    val resort by store.resort.collectAsState()
    val view = LocalView.current
    val currencyFormat = NumberFormat.getIntegerInstance(Locale.GERMANY)

    var showHireDialog by remember { mutableStateOf(false) }

    LazyColumn(
        modifier = modifier
            .fillMaxSize()
            .padding(horizontal = 16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp),
        contentPadding = PaddingValues(top = 16.dp, bottom = 96.dp)
    ) {
        // 1. Dynamic Lift Ticket Pricing Sliders
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
                                text = "Lift Ticket Pricing",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold
                            )
                            Text(
                                text = "Adjust daily pass rates to optimize attendance & yield",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                        Icon(
                            imageVector = Icons.Rounded.ConfirmationNumber,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }

                    Spacer(modifier = Modifier.height(16.dp))

                    // Adult Ticket Slider
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text("Adult Daily Pass", style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
                        Text(
                            "€${resort.ticketPriceAdult}",
                            fontFamily = FontFamily.Monospace,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.primary
                        )
                    }
                    Slider(
                        value = resort.ticketPriceAdult.toFloat(),
                        onValueChange = {
                            store.setTicketPrices(adult = it.toInt(), child = resort.ticketPriceChild)
                        },
                        valueRange = 20f..250f,
                        steps = 22
                    )

                    Spacer(modifier = Modifier.height(8.dp))

                    // Child Ticket Slider
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text("Junior / Senior Pass", style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
                        Text(
                            "€${resort.ticketPriceChild}",
                            fontFamily = FontFamily.Monospace,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.primary
                        )
                    }
                    Slider(
                        value = resort.ticketPriceChild.toFloat(),
                        onValueChange = {
                            store.setTicketPrices(adult = resort.ticketPriceAdult, child = it.toInt())
                        },
                        valueRange = 10f..150f,
                        steps = 13
                    )

                    Spacer(modifier = Modifier.height(10.dp))

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Projected Daily Ticket Revenue:", style = MaterialTheme.typography.labelSmall)
                        Text(
                            text = "€${currencyFormat.format(resort.dailyTicketRevenue)}",
                            fontFamily = FontFamily.Monospace,
                            fontWeight = FontWeight.Bold,
                            color = AlpineGreen
                        )
                    }
                }
            }
        }

        // 2. Commercial Bank & Loans Card
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
                                text = "Park City Commercial Bank",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold
                            )
                            Text(
                                text = "Outstanding Principal: €${currencyFormat.format(resort.outstandingLoan)}",
                                style = MaterialTheme.typography.bodySmall,
                                color = if (resort.outstandingLoan > 0) AlpineCoral else AlpineGreen
                            )
                        }
                        Icon(
                            imageVector = Icons.Rounded.AccountBalance,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }

                    if (resort.outstandingLoan > 0) {
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = "Daily Interest (~0.3%): €${currencyFormat.format(resort.dailyLoanInterest)}/day",
                            style = MaterialTheme.typography.labelSmall,
                            color = AlpineCoral
                        )
                    }

                    Spacer(modifier = Modifier.height(16.dp))

                    Text("Borrow Capital:", style = MaterialTheme.typography.labelMedium)
                    Spacer(modifier = Modifier.height(8.dp))
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Button(
                            onClick = {
                                view.performHapticFeedback(HapticFeedbackConstants.CONFIRM)
                                store.takeLoan(25000L)
                            },
                            modifier = Modifier.weight(1f),
                            shape = PillShape
                        ) {
                            Text("+€25k", fontSize = 12.sp)
                        }
                        Button(
                            onClick = {
                                view.performHapticFeedback(HapticFeedbackConstants.CONFIRM)
                                store.takeLoan(50000L)
                            },
                            modifier = Modifier.weight(1f),
                            shape = PillShape
                        ) {
                            Text("+€50k", fontSize = 12.sp)
                        }
                        Button(
                            onClick = {
                                view.performHapticFeedback(HapticFeedbackConstants.CONFIRM)
                                store.takeLoan(100000L)
                            },
                            modifier = Modifier.weight(1f),
                            shape = PillShape
                        ) {
                            Text("+€100k", fontSize = 12.sp)
                        }
                    }

                    if (resort.outstandingLoan > 0) {
                        Spacer(modifier = Modifier.height(12.dp))
                        OutlinedButton(
                            onClick = {
                                view.performHapticFeedback(HapticFeedbackConstants.CONFIRM)
                                store.repayLoan(25000L)
                            },
                            modifier = Modifier.fillMaxWidth(),
                            shape = PillShape,
                            enabled = resort.cash >= 25000L || (resort.cash > 0 && resort.cash >= resort.outstandingLoan)
                        ) {
                            Text("Repay Loan Principal (€25,000)")
                        }
                    }
                }
            }
        }

        // 3. Staff Roster & Management
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Resort Staff Roster (${resort.staff.size})",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )

                TextButton(
                    onClick = {
                        view.performHapticFeedback(HapticFeedbackConstants.CONTEXT_CLICK)
                        showHireDialog = true
                    }
                ) {
                    Icon(Icons.Rounded.PersonAdd, contentDescription = null, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Hire Staff")
                }
            }
        }

        items(resort.staff, key = { it.id }) { staff ->
            StaffMemberCard(
                staff = staff,
                onFire = {
                    view.performHapticFeedback(HapticFeedbackConstants.CONTEXT_CLICK)
                    store.fireStaff(staff.id)
                }
            )
        }

        // 4. Recent Transactions Ledger
        item {
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = "Recent Transactions Ledger",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
        }

        items(resort.transactions.take(15), key = { it.id }) { tx ->
            Surface(
                modifier = Modifier.fillMaxWidth(),
                shape = ExpressiveShapes.medium,
                color = MaterialTheme.colorScheme.surfaceContainer
            ) {
                Row(
                    modifier = Modifier.padding(horizontal = 14.dp, vertical = 10.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column {
                        Text(tx.title, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
                        Text("Game Day ${tx.gameDay} · ${tx.category.label}", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }

                    Text(
                        text = "${if (tx.amount >= 0) "+" else ""}€${currencyFormat.format(tx.amount)}",
                        fontFamily = FontFamily.Monospace,
                        fontWeight = FontWeight.Bold,
                        color = if (tx.amount >= 0) AlpineGreen else AlpineCoral
                    )
                }
            }
        }
    }

    // Hire Staff Dialog
    if (showHireDialog) {
        var staffName by remember { mutableStateOf("New Specialist") }
        var selectedRole by remember { mutableStateOf(StaffRole.SKI_PATROL) }

        AlertDialog(
            onDismissRequest = { showHireDialog = false },
            title = { Text("Hire Mountain Staff", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    OutlinedTextField(
                        value = staffName,
                        onValueChange = { staffName = it },
                        label = { Text("Staff Full Name") },
                        modifier = Modifier.fillMaxWidth(),
                        shape = ExpressiveShapes.medium
                    )

                    Text("Select Role & Specialty:", style = MaterialTheme.typography.labelMedium)

                    StaffRole.entries.forEach { role ->
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            RadioButton(
                                selected = selectedRole == role,
                                onClick = {
                                    selectedRole = role
                                    staffName = when (role) {
                                        StaffRole.SKI_PATROL -> "Lukas Meier"
                                        StaffRole.GROOMER -> "Hans Gruber"
                                        StaffRole.MECHANIC -> "Beat Widmer"
                                        StaffRole.INSTRUCTOR -> "Chloe Martin"
                                        StaffRole.ADMINISTRATIVE -> "Sophia Keller"
                                    }
                                }
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Column {
                                Text(role.label, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
                                Text(
                                    "Salary: €${role.defaultSalary}/day · ${role.description}",
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
                        store.hireStaff(name = staffName, role = selectedRole)
                        showHireDialog = false
                    },
                    shape = PillShape
                ) {
                    Text("Hire (€${selectedRole.defaultSalary}/day)")
                }
            },
            dismissButton = {
                TextButton(onClick = { showHireDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }
}

@Composable
fun StaffMemberCard(
    staff: StaffMember,
    onFire: () -> Unit
) {
    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = ExpressiveShapes.large,
        colors = CardDefaults.elevatedCardColors(
            containerColor = MaterialTheme.colorScheme.surfaceContainer
        )
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Surface(
                    shape = CircleShape,
                    color = MaterialTheme.colorScheme.secondaryContainer,
                    modifier = Modifier.size(40.dp)
                ) {
                    Box(contentAlignment = Alignment.Center) {
                        Text(
                            text = staff.name.split(" ").mapNotNull { it.firstOrNull()?.toString() }.joinToString(""),
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSecondaryContainer
                        )
                    }
                }

                Spacer(modifier = Modifier.width(12.dp))

                Column {
                    Text(staff.name, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                    Text(staff.role.label, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text("€${staff.salaryPerDay}/day · Morale ${staff.moralePercent}%", style = MaterialTheme.typography.labelSmall)
                }
            }

            IconButton(onClick = onFire) {
                Icon(Icons.Rounded.PersonRemove, contentDescription = "Fire Staff", tint = AlpineCoral)
            }
        }
    }
}
