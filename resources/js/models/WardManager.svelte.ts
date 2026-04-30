/**
 * WardManager Domain Model (OOD)
 * Manages state, trends, and insurance analytics for the Rawat Inap Dashboard.
 * Uses Svelte 5 Runes for reactivity.
 */

export interface TrendPoint {
    label: string;
    value: number;
    prevValue: number; 
}

export interface InsuranceSegment {
    label: string;
    value: number;
    color: string;
}

export interface Ward {
    id: string;
    name: string;
    capacity: number;
    active: number;
    admitsToday: number;
    bor: number;
    alos: number;
    status: 'available' | 'warning' | 'full';
    weeklyTrend: number[];
    vsLastWeek: number;
}

export class WardManager {
    wards        = $state<Ward[]>([]);
    isLoading    = $state(false);
    selectedYear = $state(new Date().getFullYear());
    availableYears = $state<number[]>([]);
    selectedWard = $state('');

    // Unified trend points from the database
    trendPoints = $state<TrendPoint[]>([]);

    // --- Dashboard Stats ---
    totalPasienDirawat = $state(0);
    totalPasienDirawatYearly = $state(0);
    totalBedCapacity   = $state(0);
    totalMasukToday    = $state(0);
    totalKeluarToday   = $state(0);
    bor                = $state(0);
    alos               = $state(0);
    
    // Deltas
    borDelta   = $state(0);
    alosDelta  = $state(0);

    // --- Insurance breakdown ---
    insuranceSegments: InsuranceSegment[] = $state([]);
    yearlyComparisonData = $state<any[]>([]);

    // --- Yearly comparison view ---
    yearlyComparison = $derived.by(() => {
        const data = this.yearlyComparisonData;
        if (data.length === 0) return { values: [], labels: [] };
        
        return {
            values: data.map(d => d.value),
            labels: data.map(d => d.label)
        };
    });

    constructor() {}

    loadFromDatabase(data: any) {
        if (!data) return;

        this.totalPasienDirawat = data.totalPasienDirawat || 0;
        this.totalPasienDirawatYearly = data.totalPasienDirawatYearly || 0;
        this.totalBedCapacity   = data.totalBedCapacity   || 0;
        this.totalMasukToday    = data.totalMasukToday    || 0;
        this.totalKeluarToday   = data.totalKeluarToday   || 0;
        this.bor                = data.bor                || 0;
        this.alos               = data.alos               || 0;
        this.borDelta           = data.borDelta           || 0;
        this.alosDelta          = data.alosDelta          || 0;

        this.trendPoints = data.trend || [];
        this.availableYears = data.availableYears || [new Date().getFullYear()];
        this.selectedYear = data.filters?.tahun || new Date().getFullYear();
        this.yearlyComparisonData = data.yearlyComparison || [];

        if (data.wardPerformance) {
            this.wards = [...data.wardPerformance];
        }

        if (data.insuranceBreakdown) {
            this.insuranceSegments = [...data.insuranceBreakdown];
        }
    }
}
