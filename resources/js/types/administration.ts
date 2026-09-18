export interface RoleSummary {
    id: number;
    name: string;
}

export interface AdminUserData {
    id: number;
    name: string;
    email: string;
    activo: boolean;
    created_at: string;
    roles: RoleSummary[];
}

export interface RoleListItem {
    id: number;
    name: string;
    permissions_count: number;
    users_count: number;
}

export interface RoleDetail {
    id: number;
    name: string;
}

export type PermissionGroups = Record<string, string[]>;
