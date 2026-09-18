import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
    permissions: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export type TipoDocumento = 'dni' | 'ruc' | 'ce' | 'pasaporte';

export type TipoSede = 'oficina' | 'tienda' | 'planta' | 'almacen' | 'local' | 'sucursal' | 'otra';

export interface Client {
    id: number;
    codigo: string;
    tipo_documento: TipoDocumento;
    numero_documento: string;
    razon_social: string;
    nombre_comercial: string | null;
    telefono: string | null;
    whatsapp: string | null;
    email: string | null;
    direccion_fiscal: string | null;
    departamento: string | null;
    provincia: string | null;
    distrito: string | null;
    ubigeo: string | null;
    activo: boolean;
    observaciones: string | null;
    created_at: string;
    updated_at: string;
    sites?: ClientSite[];
    vehicles?: Vehicle[];
    [key: string]: unknown;
}

export interface ClientSite {
    id: number;
    client_id: number;
    tipo: TipoSede;
    nombre: string;
    direccion: string;
    ubigeo: string | null;
    referencia: string | null;
    contacto: string | null;
    telefono: string | null;
    email: string | null;
    activo: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

export interface Vehicle {
    id: number;
    client_id: number;
    placa: string;
    marca: string | null;
    modelo: string | null;
    descripcion: string | null;
    activo: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

export interface PaginatedLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginatedLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}
