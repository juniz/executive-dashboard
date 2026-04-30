/**
 * RekamMedisManager Model
 * Manages state for Hospital Performance Indicators (BOR, LOS, NDR, GDR, BTO, TOI).
 * Uses Svelte 5 Runes for reactivity.
 */

export interface MonthlyIndicator {
    month: string;
    bor: number;
    alos: number;
    bto: number;
    toi: number;
    ndr: number;
    gdr: number;
    hp: number;
    ld: number;
    keluar: number;
}

export interface WardPerformance {
    name: string;
    beds: number;
    hp: number;
    keluar: number;
    bor: number;
    alos: number;
    bto: number;
    toi: number;
}

export class RekamMedisManager {
    isLoading = $state(false);
    selectedYear = $state(new Date().getFullYear());
    availableYears = $state<number[]>([]);
    
    // Summary of current month
    summary = $state<Partial<MonthlyIndicator>>({});
    
    // Trends over months
    monthlyTrends = $state<MonthlyIndicator[]>([]);
    
    // Performance per ward
    wardPerformance = $state<WardPerformance[]>([]);

    constructor() {}

    loadFromDatabase(data: any) {
        if (!data) return;

        this.summary = data.summary || {};
        this.monthlyTrends = data.monthlyTrends || [];
        this.wardPerformance = data.wardPerformance || [];
        this.availableYears = data.availableYears || [new Date().getFullYear()];
        this.selectedYear = data.filters?.tahun || new Date().getFullYear();
    }

    get trendLabels() {
        return this.monthlyTrends.map(t => t.month);
    }

    get borTrend() {
        return this.monthlyTrends.map(t => t.bor);
    }

    get alosTrend() {
        return this.monthlyTrends.map(t => t.alos);
    }

    get toiTrend() {
        return this.monthlyTrends.map(t => t.toi);
    }

    get btoTrend() {
        return this.monthlyTrends.map(t => t.bto);
    }

    get deathRateTrend() {
        return {
            ndr: this.monthlyTrends.map(t => t.ndr),
            gdr: this.monthlyTrends.map(t => t.gdr)
        };
    }
}
