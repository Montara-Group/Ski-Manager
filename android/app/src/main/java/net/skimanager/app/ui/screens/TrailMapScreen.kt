package net.skimanager.app.ui.screens

import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.view.HapticFeedbackConstants
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.gestures.detectTapGestures
import androidx.compose.foundation.gestures.detectTransformGestures
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.rounded.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clipToBounds
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.PathEffect
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.drawscope.drawIntoCanvas
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.IntOffset
import androidx.compose.ui.unit.IntSize
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.skimanager.app.engine.ResortStore
import net.skimanager.app.model.RealMapSegment
import net.skimanager.app.model.SlopeDifficulty
import net.skimanager.app.ui.theme.*
import java.text.NumberFormat
import java.util.Locale
import kotlin.math.roundToInt

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TrailMapScreen(
    store: ResortStore,
    modifier: Modifier = Modifier
) {
    val resort by store.resort.collectAsState()
    val context = LocalContext.current
    val view = LocalView.current
    val currencyFormat = NumberFormat.getIntegerInstance(Locale.GERMANY)

    // Load ParkCity.jpg bitmap once
    val mountainBitmap = remember {
        try {
            context.assets.open("ParkCity.jpg").use { stream ->
                BitmapFactory.decodeStream(stream)
            }
        } catch (e: Exception) {
            null
        }
    }

    var scale by remember { mutableFloatStateOf(1.0f) }
    var offsetX by remember { mutableFloatStateOf(0f) }
    var offsetY by remember { mutableFloatStateOf(0f) }

    var showAllMountainTrails by remember { mutableStateOf(false) }
    var filterMode by remember { mutableStateOf("All") }
    var selectedSegmentId by remember { mutableStateOf<Int?>(null) }

    val visibleSegments = remember(resort.realSegments, showAllMountainTrails, filterMode) {
        var list = resort.realSegments.toList()
        if (!showAllMountainTrails) {
            list = list.filter { it.isBuilt }
        }
        when (filterMode) {
            "Lifts" -> list.filter { it.isLift }
            "Slopes" -> list.filter { it.isSlope }
            "Green" -> list.filter { it.difficulty.equals("green", ignoreCase = true) }
            "Blue" -> list.filter { it.difficulty.equals("blue", ignoreCase = true) }
            "Black" -> list.filter { it.difficulty.equals("black", ignoreCase = true) || it.difficulty.equals("double_black", ignoreCase = true) }
            else -> list
        }
    }

    val selectedSegment = remember(selectedSegmentId, resort.realSegments) {
        resort.realSegments.find { it.id == selectedSegmentId }
    }

    Column(modifier = modifier.fillMaxSize().background(Color(0xFF0F172A))) {
        // Map Top Controls
        Surface(
            modifier = Modifier.fillMaxWidth(),
            color = MaterialTheme.colorScheme.surfaceContainerHigh,
            tonalElevation = 3.dp
        ) {
            Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    SingleChoiceSegmentedButtonRow(modifier = Modifier.weight(1f)) {
                        SegmentedButton(
                            shape = SegmentedButtonDefaults.itemShape(index = 0, count = 2),
                            onClick = { showAllMountainTrails = false },
                            selected = !showAllMountainTrails
                        ) {
                            Text("Built Trails", fontSize = 12.sp)
                        }
                        SegmentedButton(
                            shape = SegmentedButtonDefaults.itemShape(index = 1, count = 2),
                            onClick = { showAllMountainTrails = true },
                            selected = showAllMountainTrails
                        ) {
                            Text("All Trails (Build)", fontSize = 12.sp)
                        }
                    }

                    Spacer(modifier = Modifier.width(12.dp))

                    IconButton(
                        onClick = {
                            view.performHapticFeedback(HapticFeedbackConstants.CONTEXT_CLICK)
                            scale = 1.0f
                            offsetX = 0f
                            offsetY = 0f
                        }
                    ) {
                        Icon(
                            imageVector = Icons.Rounded.Refresh,
                            contentDescription = "Reset Zoom",
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))

                // Difficulty Filter Pills
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    listOf("All", "Lifts", "Slopes", "Green", "Blue", "Black").forEach { item ->
                        FilterChip(
                            selected = filterMode == item,
                            onClick = {
                                view.performHapticFeedback(HapticFeedbackConstants.CLOCK_TICK)
                                filterMode = item
                            },
                            label = { Text(item, fontSize = 11.sp, fontWeight = FontWeight.SemiBold) },
                            shape = PillShape
                        )
                    }
                }
            }
        }

        // Map Viewport (Leaflet Canvas)
        Box(
            modifier = Modifier
                .weight(1f)
                .fillMaxWidth()
                .clipToBounds()
                .background(Color(0xFF0A0F1D))
                .pointerInput(Unit) {
                    detectTransformGestures { _, pan, zoom, _ ->
                        scale = (scale * zoom).coerceIn(0.8f, 4.0f)
                        offsetX += pan.x
                        offsetY += pan.y
                    }
                }
        ) {
            Canvas(
                modifier = Modifier
                    .fillMaxSize()
                    .pointerInput(visibleSegments) {
                        detectTapGestures { tapOffset ->
                            // Find closest segment within tap radius
                            val w = size.width.toFloat()
                            val h = (w / (600f / 340f))

                            var closestSeg: RealMapSegment? = null
                            var minDistance = 45f // tap threshold

                            visibleSegments.forEach { seg ->
                                seg.points.forEach { pt ->
                                    val sx = (pt[1].toFloat() / 600f) * w * scale + offsetX
                                    val sy = ((340f - pt[0].toFloat()) / 340f) * h * scale + offsetY
                                    val dx = sx - tapOffset.x
                                    val dy = sy - tapOffset.y
                                    val dist = kotlin.math.sqrt(dx * dx + dy * dy)
                                    if (dist < minDistance) {
                                        minDistance = dist
                                        closestSeg = seg
                                    }
                                }
                            }

                            if (closestSeg != null) {
                                view.performHapticFeedback(HapticFeedbackConstants.CONTEXT_CLICK)
                                selectedSegmentId = closestSeg?.id
                            }
                        }
                    }
            ) {
                val canvasWidth = size.width
                val mapAspect = 600f / 340f
                val canvasHeight = canvasWidth / mapAspect

                // Draw Background Bitmap
                mountainBitmap?.let { bmp ->
                    val imageBitmap = bmp.asImageBitmap()
                    drawImage(
                        image = imageBitmap,
                        dstOffset = IntOffset(offsetX.roundToInt(), offsetY.roundToInt()),
                        dstSize = IntSize((canvasWidth * scale).roundToInt(), (canvasHeight * scale).roundToInt())
                    )
                }

                // Render Leaflet Database Segments
                visibleSegments.forEach { seg ->
                    if (seg.points.size >= 2) {
                        val path = Path()
                        val first = seg.points.first()
                        val sx = (first[1].toFloat() / 600f) * canvasWidth * scale + offsetX
                        val sy = ((340f - first[0].toFloat()) / 340f) * canvasHeight * scale + offsetY
                        path.moveTo(sx, sy)

                        for (i in 1 until seg.points.size) {
                            val pt = seg.points[i]
                            val px = (pt[1].toFloat() / 600f) * canvasWidth * scale + offsetX
                            val py = ((340f - pt[0].toFloat()) / 340f) * canvasHeight * scale + offsetY
                            path.lineTo(px, py)
                        }

                        val isSelected = seg.id == selectedSegmentId
                        val segColor = when {
                            seg.isLift -> SlopeLiftOrange
                            seg.difficulty.equals("green", ignoreCase = true) -> SlopeGreen
                            seg.difficulty.equals("blue", ignoreCase = true) -> SlopeBlue
                            seg.difficulty.equals("black", ignoreCase = true) -> SlopeBlack
                            seg.difficulty.equals("double_black", ignoreCase = true) -> SlopeDoubleBlack
                            else -> SlopeBlue
                        }

                        if (seg.isBuilt) {
                            // Dark halo for contrast
                            drawPath(
                                path = path,
                                color = Color.Black.copy(alpha = 0.5f),
                                style = Stroke(
                                    width = if (isSelected) 10f * scale else 6f * scale,
                                    cap = androidx.compose.ui.graphics.StrokeCap.Round,
                                    join = androidx.compose.ui.graphics.StrokeJoin.Round
                                )
                            )
                            // Solid trail line
                            drawPath(
                                path = path,
                                color = segColor,
                                style = Stroke(
                                    width = if (isSelected) 7f * scale else 4f * scale,
                                    cap = androidx.compose.ui.graphics.StrokeCap.Round,
                                    join = androidx.compose.ui.graphics.StrokeJoin.Round
                                )
                            )
                        } else {
                            // Unbuilt trail dashed purple line
                            drawPath(
                                path = path,
                                color = Color(0xFFA855F7),
                                style = Stroke(
                                    width = if (isSelected) 6f * scale else 3.5f * scale,
                                    cap = androidx.compose.ui.graphics.StrokeCap.Round,
                                    pathEffect = PathEffect.dashPathEffect(floatArrayOf(12f, 8f), 0f)
                                )
                            )
                        }

                        // Terminal circles for lifts
                        if (seg.isLift) {
                            val last = seg.points.last()
                            val ex = (last[1].toFloat() / 600f) * canvasWidth * scale + offsetX
                            val ey = ((340f - last[0].toFloat()) / 340f) * canvasHeight * scale + offsetY

                            drawCircle(color = segColor, radius = 6f * scale, center = Offset(sx, sy))
                            drawCircle(color = Color.White, radius = 6f * scale, center = Offset(sx, sy), style = Stroke(1.5f))
                            drawCircle(color = segColor, radius = 6f * scale, center = Offset(ex, ey))
                            drawCircle(color = Color.White, radius = 6f * scale, center = Offset(ex, ey), style = Stroke(1.5f))
                        }

                        // Selection highlight
                        if (isSelected) {
                            drawPath(
                                path = path,
                                color = Color.White.copy(alpha = 0.9f),
                                style = Stroke(
                                    width = 2f * scale,
                                    cap = androidx.compose.ui.graphics.StrokeCap.Round
                                )
                            )
                        }
                    }
                }
            }

            // Quick Trail Chips Carousel overlay at bottom of canvas
            LazyRow(
                modifier = Modifier
                    .align(Alignment.BottomStart)
                    .fillMaxWidth()
                    .padding(horizontal = 12.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(visibleSegments.take(20)) { seg ->
                    val isSel = seg.id == selectedSegmentId
                    Surface(
                        onClick = {
                            view.performHapticFeedback(HapticFeedbackConstants.KEYBOARD_TAP)
                            selectedSegmentId = seg.id
                        },
                        shape = PillShape,
                        color = if (isSel) MaterialTheme.colorScheme.primary else Color(0xCC1E293B),
                        tonalElevation = 4.dp
                    ) {
                        Row(
                            modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Surface(
                                shape = CircleShape,
                                color = if (seg.isLift) SlopeLiftOrange else SlopeBlue,
                                modifier = Modifier.size(8.dp)
                            ) {}
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(
                                text = seg.name,
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White
                            )
                        }
                    }
                }
            }
        }

        // Segment Inspector Bottom Panel
        if (selectedSegment != null) {
            Surface(
                modifier = Modifier.fillMaxWidth(),
                shape = ExpressiveShapes.large,
                color = MaterialTheme.colorScheme.surfaceContainerHigh,
                tonalElevation = 6.dp
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Surface(
                                    shape = CircleShape,
                                    color = if (selectedSegment.isLift) SlopeLiftOrange else SlopeBlue,
                                    modifier = Modifier.size(10.dp)
                                ) {}
                                Spacer(modifier = Modifier.width(6.dp))
                                Text(
                                    text = selectedSegment.name,
                                    style = MaterialTheme.typography.titleMedium,
                                    fontWeight = FontWeight.Bold
                                )
                            }
                            Text(
                                text = "${selectedSegment.type.replaceFirstChar { it.uppercase() }} · ${selectedSegment.lengthMeters}m · Sector ${selectedSegment.sector}",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }

                        IconButton(onClick = { selectedSegmentId = null }) {
                            Icon(Icons.Rounded.Close, contentDescription = "Close Inspector")
                        }
                    }

                    Spacer(modifier = Modifier.height(12.dp))

                    if (selectedSegment.isBuilt) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column {
                                Text(
                                    text = if (selectedSegment.isLift) "Mechanical Durability: ${selectedSegment.conditionPercent}%" else "Grooming Quality: ${selectedSegment.groomingPercent}%",
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.Medium
                                )
                                Text(
                                    text = if (selectedSegment.isOpen) "Open to Skiers" else "Closed for Maintenance",
                                    fontSize = 11.sp,
                                    color = if (selectedSegment.isOpen) AlpineGreen else AlpineCoral
                                )
                            }

                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Text(if (selectedSegment.isOpen) "Open" else "Closed", fontSize = 12.sp)
                                Spacer(modifier = Modifier.width(8.dp))
                                Switch(
                                    checked = selectedSegment.isOpen,
                                    onCheckedChange = {
                                        view.performHapticFeedback(HapticFeedbackConstants.CONTEXT_CLICK)
                                        store.toggleRealSegmentOpen(selectedSegment.id)
                                    }
                                )
                            }
                        }
                    } else {
                        // Unbuilt - Option to Build
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column {
                                Text(
                                    text = "Construction Cost",
                                    fontSize = 11.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                                Text(
                                    text = "€${currencyFormat.format(selectedSegment.constructionCost)}",
                                    fontSize = 15.sp,
                                    fontFamily = FontFamily.Monospace,
                                    fontWeight = FontWeight.Bold,
                                    color = AlpineGreen
                                )
                            }

                            Button(
                                onClick = {
                                    view.performHapticFeedback(HapticFeedbackConstants.CONFIRM)
                                    store.buildRealSegment(selectedSegment.id)
                                },
                                shape = PillShape,
                                colors = ButtonDefaults.buttonColors(
                                    containerColor = MaterialTheme.colorScheme.primary
                                ),
                                enabled = resort.cash >= selectedSegment.constructionCost
                            ) {
                                Text(if (selectedSegment.isLift) "Construct Lift" else "Carve Trail")
                            }
                        }
                    }
                }
            }
        }
    }
}
