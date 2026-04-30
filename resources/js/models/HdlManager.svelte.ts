/**
 * HdlManager Domain Model (OOD)
 * Manages state, trends, and insurance analytics for the Hemodialysis Dashboard.
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

export interface PatientHD {
    id: string;
    patient: string;
    status: string;
    statusLabel: string;
    statusColor: 'active' | 'break' | 'closed';
    duration: string;
    access: string;
    dialyzer: string;
    qb: string;
    qd: string;
}

export class HdlManager {
    patients      = $state<PatientHD[]>([]);
    isLoading     = $state(false);
    selectedYear  = $state(new Date().getFullYear());
    availableYears = $state<number[]>([]);

    // Unified trend points from the database
    trendPoints = $state<TrendPoint[]>([]);

    // --- Dashboard Stats ---
    totalVisitsToday = $state(0);
    totalVisitsYearly = $state(0);
    finishedToday    = $state(0);
    waitingToday     = $state(0);
    avgDuration      = $state(0);
    
    // Deltas
    visitsDelta   = $state(0);

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

        this.totalVisitsToday = data.totalVisitsToday || 0;
        this.totalVisitsYearly = data.totalVisitsYearly || 0;
        this.finishedToday    = data.finishedToday    || 0;
        this.waitingToday     = data.waitingToday     || 0;
        this.avgDuration      = data.avgDuration      || 0;
        this.visitsDelta      = data.visitsDelta      || 0;

        this.trendPoints = data.trend || [];
        this.availableYears = data.availableYears || [new Date().getFullYear()];
        this.selectedYear = data.filters?.tahun || new Date().getFullYear();
        this.yearlyComparisonData = data.yearlyComparison || [];

        if (data.patientPerformance) {
            this.patients = [...data.patientPerformance];
        }

        if (data.insuranceBreakdown) {
            this.insuranceSegments = [...data.insuranceBreakdown];
        }
    }
}
