export type TicketPriority = 'low' | 'medium' | 'high' | 'critical';
export type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed';

export interface Ticket {
    id: number;
    reference: string;
    requester_name: string;
    requester_email: string;
    subject: string;
    description: string;
    priority: TicketPriority;
    status: TicketStatus;
    due_at: string | null;
    resolved_at: string | null;
    sla_breached: boolean;
    created_at: string;
}

export interface DashboardMetrics {
    total: number;
    open: number;
    in_progress: number;
    resolved: number;
    critical: number;
    sla_breached: number;
}
