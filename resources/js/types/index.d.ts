export interface Role {
    id: number;
    key: string;
    name: string;
    description?: string | null;
}

export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    photo_url?: string | null;
    pin?: string | null;
    verification_code?: string | null;
    sex?: string | null;
    sex_label?: string | null;
    email_verified_at?: string | null;
    role?: Role | null;
}

export interface ManagedUser extends User {
    role_id?: number | null;
    is_current_user?: boolean;
    created_at?: string | null;
}

export interface CampLot {
    id: number;
    name: string;
    amount_cents: number;
    amount: string;
    sort_order?: number;
}

export interface Camp {
    id: number;
    name: string;
    description?: string | null;
    address?: string | null;
    starts_on: string;
    starts_on_label?: string;
    ends_on?: string | null;
    ends_on_label?: string | null;
    date_range_label?: string | null;
    amount_cents: number;
    amount: string;
    is_active?: boolean;
    lots: CampLot[];
    payments_total?: number;
    payments_paid?: number;
    payments_partial?: number;
    payments_pending?: number;
    payments_exempted?: number;
    rooms_total?: number;
    teams_total?: number;
    verification_points_total?: number;
    verification_entries_total?: number;
}

export interface CampPayment {
    id: number;
    status: string;
    status_label: string;
    camp_lot_id?: number | null;
    amount_cents: number;
    amount: string;
    installments_count: number;
    paid_installments: number;
    amount_paid_cents: number;
    amount_paid: string;
    notes?: string | null;
    paid_at?: string | null;
    exempted_at?: string | null;
    user: User;
    lot?: {
        id: number;
        name: string;
        amount: string;
    } | null;
}

export interface StatusOption {
    value: string;
    label: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
        can: {
            manageUsers: boolean;
            manageCamps: boolean;
            managePayments: boolean;
            manageOperations: boolean;
        };
    };
    flash?: {
        status?: string | null;
    };
};
