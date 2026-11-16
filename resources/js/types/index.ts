import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User | null;
    super?: { id: number; name: string; email: string } | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface AppSettings {
    company_name: string;
    logo_url: string | null;
    brand_color: string;
    timezone: string;
    low_data_mode: boolean;
    language: 'en' | 'ar' | 'ckb';
    currency: string;
}

export interface SharedData {
    name: string;
    auth: Auth;
    settings: AppSettings;
    tenant?: { id: number; name: string; slug: string } | null;
    balance: number | null;
    flash: {
        success?: string | null;
        error?: string | null;
        token?: string | null;
        migrate_output?: string | null;
        migrate_scope?: string | null;
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'customer';
    customer_id: number | null;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export type Filters = Record<string, string | number | undefined>;

export interface Account {
    id: number;
    provider: string;
    region: string;
    type: string | null;
    category: string | null;
    username: string;
    company: string | null;
    account_number: string | null;
    external_id: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    balance_due: string;
    status: string;
    action_status: string;
    tags: string[] | null;
    notes: string | null;
    membership_plan: string | null;
    next_billing_date: string | null;
    login_notice_title: string | null;
    login_notice_description: string | null;
    login_notice_button: string | null;
    last_synced_at: string | null;
    updated_at: string;
    created_at: string;
    deleted_at: string | null;
    invoices_count?: number;
    paid_invoices_count?: number;
    unpaid_invoices_count?: number;
    customers_count?: number;
    brokers_count?: number;
    customers?: (Customer & { pivot?: { assigned_at: string } })[];
    brokers?: (Broker & { pivot?: { assigned_at: string } })[];
    documents?: { id: number; group: string; name: string; path: string | null; url: string | null; created_at: string }[];
}

export interface Customer {
    id: number;
    name: string;
    code: string | null;
    login_code?: string | null;
    email: string | null;
    phone_code: string | null;
    phone: string | null;
    address: string | null;
    bid_limit: string | null;
    car_limit: number | null;
    credit_limit: string | null;
    tags: string[] | null;
    notes: string | null;
    can_bid: boolean;
    login_notice_title: string | null;
    login_notice_description: string | null;
    created_at: string;
    deleted_at: string | null;
    accounts?: Account[];
    invoices_count?: number;
    paid_invoices_count?: number;
    unpaid_invoices_count?: number;
    user?: { id: number; email: string; name: string } | null;
}

export interface Broker {
    id: number;
    name: string;
    email: string | null;
    covered: boolean;
    deleted_at: string | null;
    accounts?: Account[];
}

export interface Bid {
    id: number;
    account_id: number | null;
    customer_id: number | null;
    provider: string;
    lot_number: string;
    vin: string | null;
    description: string | null;
    image_url: string | null;
    location: string | null;
    type: string;
    phase: string;
    amount: string;
    max_bid: string | null;
    current_bid: string | null;
    status: string;
    sale_date: string | null;
    ip_address: string | null;
    app: string | null;
    placed_at: string | null;
    account?: Account | null;
    customer?: Customer | null;
}

export interface WatchItem {
    id: number;
    provider: string;
    lot_number: string;
    vin: string | null;
    description: string | null;
    image_url: string | null;
    sale_status: string | null;
    auction_status: string | null;
    sale_date: string | null;
    added_at: string | null;
    account?: Account | null;
    customer?: Customer | null;
}

export interface Activity {
    id: number;
    type: string;
    provider: string | null;
    target: string | null;
    description: string | null;
    amount: string | null;
    ip_address: string | null;
    app: string | null;
    created_at: string;
    account?: Account | null;
    customer?: Customer | null;
}

export interface Invoice {
    id: number;
    account_id: number | null;
    provider: string | null;
    invoice_number: string;
    vin: string | null;
    description: string | null;
    location: string | null;
    image_url: string | null;
    type: string;
    status: string;
    amount: string;
    amount_due: string;
    sale_price: string | null;
    fees: string | null;
    invoice_date: string | null;
    due_date: string | null;
    paid_at: string | null;
    won_by_customer_id: number | null;
    notes: string | null;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
    account?: Account | null;
    won_by?: Customer | null;
}

export interface AnalyticsData {
    range: { from: string; to: string };
    metrics: Record<string, number>;
    daily: Record<string, number | string>[];
    purchased: { all: { name: string; count: number; value: number }[]; inside: { name: string; count: number; value: number }[] };
    top_customers: { id: number; label: string; actions: number; bids: number; won: number }[];
    top_accounts: { id: number; label: string; actions: number; bids: number; won: number }[];
    top_vehicles: { lot: string; description: string | null; views: number; bids: number; actions: number }[];
    by_type: { type: string; label: string; count: number }[];
    by_app: { name: string; count: number }[];
    by_provider: { name: string; count: number }[];
}
