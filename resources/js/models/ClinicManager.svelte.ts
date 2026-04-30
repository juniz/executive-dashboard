/**
 * ClinicManager Domain Model (OOD)
 * Manages state, trends, and insurance analytics for the Rawat Jalan Dashboard.
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

export interface Clinic {
    id: string;
    name: string;
    doctor: string;
    status: 'active' | 'break' | 'closed';
    queueCount: number;
    avgWaitTime: number;
    totalVisitsToday: number;
    capacityPercent: number;
    weeklyTrend: number[];
    vsLastWeek: number;
}

export class ClinicManager {
    clinics      = $state<Clinic[]>([]);
    isLoading    = $state(false);
    selectedYear = $state(new Date().getFullYear());
    availableYears = $state<number[]>([]);
    selectedClinic = $state('');

    // Unified trend points from the database
    trendPoints = $state<TrendPoint[]>([]);

    // --- Dashboard Stats ---
    totalVisitsToday      = $state(0);
    totalVisitsYearly     = $state(0);
    totalQueueCount        = $state(0);
    averageWaitTimeOverall = $state(0);
    overallEfficiency      = $state(0);
    totalClinicsCount      = $state(0);
    activeClinicsCount     = $state(0);
    
    // Deltas
    visitsDelta   = $state(0);
    queueDelta    = $state(0);
    waitTimeDelta = $state(0);

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

        this.totalVisitsToday      = data.totalVisitsToday      || 0;
        this.totalVisitsYearly     = data.totalVisitsYearly     || 0;
        this.totalQueueCount        = data.totalQueueCount        || 0;
        this.averageWaitTimeOverall = data.avgWaitTimeOverall || 0;
        this.overallEfficiency      = data.overallEfficiency      || 0;
        this.totalClinicsCount      = data.totalClinicsCount      || 0;
        this.activeClinicsCount     = data.activeClinicsCount     || 0;
        this.visitsDelta            = data.visitsDelta            || 0;
        this.queueDelta             = data.queueDelta             || 0;
        this.waitTimeDelta          = data.waitTimeDelta          || 0;

        this.trendPoints = data.trend || [];
        this.availableYears = data.availableYears || [new Date().getFullYear()];
        this.selectedYear = data.filters?.tahun || new Date().getFullYear();
        this.yearlyComparisonData = data.yearlyComparison || [];

        if (data.clinicPerformance) {
            this.clinics = [...data.clinicPerformance];
        }

        if (data.insuranceBreakdown) {
            this.insuranceSegments = [...data.insuranceBreakdown];
        }
    }
}
