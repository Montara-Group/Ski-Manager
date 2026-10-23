// swift-tools-version: 5.9
import PackageDescription

let package = Package(
    name: "SkiManagerApp",
    platforms: [
        .iOS(.v17),
        .macOS(.v14)
    ],
    products: [
        .executable(
            name: "SkiManagerApp",
            targets: ["SkiManagerApp"]
        )
    ],
    dependencies: [],
    targets: [
        .executableTarget(
            name: "SkiManagerApp",
            dependencies: [],
            path: "Sources/SkiManagerApp"
        )
    ]
)
