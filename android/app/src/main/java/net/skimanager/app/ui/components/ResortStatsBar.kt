package net.skimanager.app.ui.components

import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.rounded.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.skimanager.app.model.Resort
import net.skimanager.app.ui.theme.*
import java.text.NumberFormat
import java.util.Locale

@Composable
fun ResortStatsBar(
    resort: Resort,
    isOnline: Boolean,
    isSyncing: Boolean,
    statusText: String,
    modifier: Modifier = Modifier
) {
    val currencyFormat = NumberFormat.getIntegerInstance(Locale.GERMANY)

    Surface(
        modifier = modifier.fillMaxWidth(),
        color = MaterialTheme.colorScheme.surfaceContainerHigh,
        tonalElevation = 3.dp
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(vertical = 8.dp)
        ) {
            // Live Server Status Badge
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 2.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Surface(
                        shape = CircleShape,
                        color = when {
                            isSyncing -> AlpineGold
                            isOnline -> AlpineGreen
                            else -> MaterialTheme.colorScheme.outline
                        },
                        modifier = Modifier.size(8.dp)
                    ) {}
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = when {
                            isSyncing -> "Syncing with ski-manager.net..."
                            isOnline -> "Online · ski-manager.net"
                            else -> "Offline (Cached Data)"
                        },
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                Text(
                    text = "Day ${resort.gameDay} / ${resort.totalSeasonDays}",
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary
                )
            }

            Spacer(modifier = Modifier.height(6.dp))

            // Horizontal Scrolling Snapshot Pills
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState())
                    .padding(horizontal = 12.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Cash Balance Pill
                StatsPill(
                    icon = Icons.Rounded.AccountBalanceWallet,
                    label = "Cash",
                    value = "€${currencyFormat.format(resort.cash)}",
                    containerColor = AlpineGreen.copy(alpha = 0.15f),
                    contentColor = if (MaterialTheme.colorScheme.background == SurfaceDark) AlpineGreen else AlpineGreenDark
                )

                // Daily Visitors Pill
                StatsPill(
                    icon = Icons.Rounded.Groups,
                    label = "Visitors",
                    value = "${resort.totalVisitorsToday} skiers",
                    containerColor = AlpineSkyBlue.copy(alpha = 0.15f),
                    contentColor = PrimaryLight
                )

                // Net Daily Profit Pill
                val profit = resort.netDailyProfit
                val profitColor = if (profit >= 0) AlpineGreen else AlpineCoral
                StatsPill(
                    icon = if (profit >= 0) Icons.Rounded.TrendingUp else Icons.Rounded.TrendingDown,
                    label = "Net Profit",
                    value = "${if (profit >= 0) "+" else ""}€${currencyFormat.format(profit)}",
                    containerColor = profitColor.copy(alpha = 0.15f),
                    contentColor = profitColor
                )

                // Mountain Weather Pill
                StatsPill(
                    icon = Icons.Rounded.AcUnit,
                    label = "Temp",
                    value = "${resort.weather.temperatureCelsius.toInt()}°C ${resort.weather.condition.iconEmoji}",
                    containerColor = MaterialTheme.colorScheme.secondaryContainer.copy(alpha = 0.5f),
                    contentColor = MaterialTheme.colorScheme.onSecondaryContainer
                )

                // Génépis Pill
                StatsPill(
                    icon = Icons.Rounded.Eco,
                    label = "Génépis",
                    value = "${resort.genepis} 🌿",
                    containerColor = AlpineGold.copy(alpha = 0.15f),
                    contentColor = AlpineGold
                )
            }
        }
    }
}

@Composable
fun StatsPill(
    icon: ImageVector,
    label: String,
    value: String,
    containerColor: Color,
    contentColor: Color,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier,
        shape = PillShape,
        color = containerColor
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                imageVector = icon,
                contentDescription = null,
                tint = contentColor,
                modifier = Modifier.size(16.dp)
            )
            Spacer(modifier = Modifier.width(6.dp))
            Column {
                Text(
                    text = label,
                    fontSize = 9.sp,
                    fontWeight = FontWeight.Medium,
                    color = contentColor.copy(alpha = 0.8f)
                )
                Text(
                    text = value,
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Bold,
                    fontFamily = FontFamily.Monospace,
                    color = contentColor
                )
            }
        }
    }
}
