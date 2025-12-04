<x-layouts.app>
    <x-layouts.navigation.topbar />
    <x-layouts.navigation.sidebar />
    <main class='content pb-4'>
        <x-layouts.navigation.navbar />
        {{$slot}}
    </main>

    <!-- Global Search Component -->
    @livewire('components.global-search')

    @push('styles')
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .metric-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .metric-card-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .metric-card-warning {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .metric-card-info {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .chart-container canvas {
            max-width: 100% !important;
            max-height: 100% !important;
        }

        /* Fix chart sizing issues */
        canvas {
            max-width: 100% !important;
            max-height: 100% !important;
        }

        .heatmap-day {
            transition: transform 0.2s ease-in-out;
        }

        .heatmap-day:hover {
            transform: scale(1.2);
            z-index: 10;
            position: relative;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }

        .icon-shape {
            transition: transform 0.2s ease-in-out;
        }

        .icon-shape:hover {
            transform: scale(1.1);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Department Modal Styles */
        .department-modal-overlay {
            backdrop-filter: blur(2px);
        }

        .department-detail-card {
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        .department-detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .department-row {
            transition: all 0.2s ease-in-out;
            border-radius: 8px;
            padding: 8px;
            margin-bottom: 4px;
        }

        .department-row:hover {
            background-color: rgba(0, 123, 255, 0.05);
            transform: translateX(4px);
        }

        /* Ranking badge styles */
        .ranking-badge {
            position: absolute;
            top: -5px;
            left: -5px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            color: white;
            border: 2px solid white;
        }

        /* Modal responsive adjustments */
        @media (max-width: 576px) {
            .department-modal-content {
                width: 100% !important;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Chartist CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chartist@1.3.0/dist/chartist.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chartist@1.3.0/dist/chartist.min.css">

    @endpush
</x-layouts.app>