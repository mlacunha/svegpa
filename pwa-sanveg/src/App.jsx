import React, { useState, useEffect, useLayoutEffect } from 'react';
import {
  Leaf, MapPin, Activity, CheckCircle, AlertTriangle, AlertCircle, Users, Home,
  Settings, Menu, X, Wifi, WifiOff, FileText, Download, Check,
  ChevronDown, ChevronUp, PieChart, Database, LogOut, User, Folder, ClipboardList, Building, Briefcase, Map, BookOpen, Bookmark, Search,
  Bell, ChevronRight, ChevronLeft, Expand
} from 'lucide-react';
import { MapContainer, TileLayer, CircleMarker, Popup, Tooltip } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import clsx from 'clsx';
import axios from 'axios';
import { db } from './db';
import { useLiveQuery } from 'dexie-react-hooks';

import InteractiveMap from './InteractiveMap';
import ProgramasCRUD from './ProgramasCRUD';
import HospedeirosCRUD from './HospedeirosCRUD';
import NormasCRUD from './NormasCRUD';
import PropriedadesCRUD from './PropriedadesCRUD';
import ProdutoresCRUD from './ProdutoresCRUD';
import TiposOrgaoCRUD from './TiposOrgaoCRUD';
import OrgaosCRUD from './OrgaosCRUD';
import UnidadesCRUD from './UnidadesCRUD';
import CargosCRUD from './CargosCRUD';
import UsuariosCRUD from './UsuariosCRUD';
import InspecaoCRUD from './InspecaoCRUD';
import AmostragemCRUD from './AmostragemCRUD';
import SyncManager from './SyncManager';
import Login from './Login';
import HistoricoLevantamentos from './HistoricoLevantamentos';

/* ─────────────────────────────────────────────
   BOTTOM NAV — Mobile only, always visible
───────────────────────────────────────────── */
function MobileBottomNav({ currentRoute, setCurrentRoute }) {
  const items = [
    { route: 'dashboard', icon: <PieChart size={22} />, label: 'Principal' },
    { route: 'produtores', icon: <Users size={22} />, label: 'Produtores' },
    { route: 'propriedades', icon: <Home size={22} />, label: 'Propriedades' },
    { route: 'inspecao', icon: <FileText size={22} />, label: 'Inspeções' },
  ];

  return (
    <nav className="md:hidden fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-slate-200 flex items-center justify-around px-2 safe-bottom"
      style={{ paddingBottom: 'env(safe-area-inset-bottom, 0px)' }}>
      {items.map(item => (
        <button
          key={item.route}
          onClick={() => setCurrentRoute(item.route)}
          className={clsx(
            'flex flex-col items-center justify-center gap-0.5 py-2.5 px-3 rounded-xl transition-colors flex-1',
            currentRoute === item.route
              ? 'text-brand-blue'
              : 'text-slate-400 hover:text-slate-600'
          )}
        >
          {item.icon}
          <span className="text-[10px] font-semibold leading-tight">{item.label}</span>
        </button>
      ))}
    </nav>
  );
}

/* ─────────────────────────────────────────────
   MOBILE TOP HEADER
───────────────────────────────────────────── */
function MobileHeader({ onMenuClick, onProfileClick, title, subtitle }) {
  return (
    <header className="md:hidden fixed top-0 left-0 right-0 z-30 bg-white border-b border-slate-100 flex items-center justify-between px-4 h-14">
      <button onClick={onMenuClick} className="p-1.5 -ml-1.5 text-slate-600 hover:text-brand-blue transition-colors">
        <Menu size={24} />
      </button>

      <div className="flex-1 text-center">
        <p className="text-base font-bold text-slate-800 leading-tight">
          {title || 'Dashboard'}
        </p>
        <p className="text-[10px] text-slate-400 leading-tight">{subtitle || 'Inspeções Fitossanitárias'}</p>
      </div>

      <div className="flex items-center gap-1">
        <button className="relative p-1.5 text-slate-600 hover:text-brand-blue transition-colors">
          <Bell size={22} />
          <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full border border-white" />
        </button>
        <button onClick={onProfileClick} className="p-1.5 text-slate-600 hover:text-brand-blue transition-colors">
          <User size={22} />
        </button>
      </div>
    </header>
  );
}

/* ─────────────────────────────────────────────
   MOBILE STAT CARD
───────────────────────────────────────────── */
function MobileStatCard({ title, value, icon, bgClass, iconBgClass, textColor = 'text-slate-800', fullWidth = false }) {
  return (
    <div className={clsx(
      'rounded-2xl p-4 flex flex-row items-center gap-4',
      bgClass,
      fullWidth ? 'col-span-2' : ''
    )}>
      <div className={clsx('w-10 h-10 rounded-xl flex flex-shrink-0 items-center justify-center', iconBgClass)}>
        {icon}
      </div>
      <div className="flex flex-col min-w-0">
        <p className="text-xs font-semibold text-slate-500 mb-0.5 whitespace-nowrap overflow-hidden text-ellipsis">{title}</p>
        <p className={clsx('text-2xl font-extrabold tracking-tight', textColor)}>{value}</p>
      </div>
    </div>
  );
}

/* ─────────────────────────────────────────────
   MOBILE DASHBOARD VIEW
───────────────────────────────────────────── */
function MobileDashboard({ dashboardData, setCurrentRoute, isFullscreenMap, setIsFullscreenMap, syncDashboard }) {
  const [activeTab, setActiveTab] = useState('programas');
  const [isMapOpen, setIsMapOpen] = useState(false);

  const stats = dashboardData.stats;
  const totalRegistros = (stats.count_normal || 0) + (stats.count_suspeita || 0) + (stats.count_foco || 0);

  const tabItems = {
    programas: dashboardData.recent_programas || [],
    propriedades: dashboardData.recent_propriedades || [],
    produtores: dashboardData.recent_produtores || [],
  };

  const tabRoutes = {
    programas: 'programas',
    propriedades: 'propriedades',
    produtores: 'produtores',
  };

  const tabLabels = {
    programas: 'Programas Recentes',
    propriedades: 'Propriedades Recentes',
    produtores: 'Produtores Recentes',
  };

  return (
    <div className="flex flex-col gap-5 pb-4">

      {/* ── Sync button mobile ── */}
      <div className="flex justify-end">
        <SyncManager onSyncComplete={syncDashboard} />
      </div>

      {/* ── Visão Geral ── */}
      <section>
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-lg font-bold text-slate-800">Visão Geral</h2>
          <span className="text-xs text-slate-400 font-medium">{totalRegistros} Registros</span>
        </div>

        <div className="grid grid-cols-2 gap-3">
          {/* Programas */}
          <MobileStatCard
            title="Programas"
            value={stats.total_programas ?? 0}
            icon={<FileText size={20} className="text-brand-blue" />}
            bgClass="bg-blue-50"
            iconBgClass="bg-brand-blue/15"
          />
          {/* Municípios */}
          <MobileStatCard
            title="Municípios"
            value={stats.total_municipios ?? 0}
            icon={<MapPin size={20} className="text-blue-500" />}
            bgClass="bg-sky-50"
            iconBgClass="bg-sky-200/60"
          />
          {/* Normal */}
          <MobileStatCard
            title="Normal"
            value={stats.count_normal ?? 0}
            icon={<CheckCircle size={20} className="text-emerald-500" />}
            bgClass="bg-emerald-50"
            iconBgClass="bg-emerald-200/60"
            textColor="text-emerald-700"
          />
          {/* Suspeita */}
          <MobileStatCard
            title="Suspeita"
            value={stats.count_suspeita ?? 0}
            icon={<AlertTriangle size={20} className="text-amber-500" />}
            bgClass="bg-amber-50"
            iconBgClass="bg-amber-200/60"
            textColor="text-amber-600"
          />
          {/* Foco Detectado — full width */}
          <MobileStatCard
            title="Foco Detectado"
            value={stats.count_foco ?? 0}
            icon={<AlertCircle size={20} className="text-red-500" />}
            bgClass="bg-red-50"
            iconBgClass="bg-red-200/60"
            textColor="text-red-600"
            fullWidth
          />
        </div>
      </section>

      {/* ── Mapa de Levantamentos ── */}
      <section className="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <button
          onClick={() => setIsMapOpen(!isMapOpen)}
          className="w-full flex items-center justify-between p-4 focus:outline-none bg-white hover:bg-slate-50 transition-colors"
        >
          <div className="flex items-center gap-3">
            <div className="p-2 rounded-xl bg-blue-50 text-blue-500">
              <Map size={20} />
            </div>
            <h2 className="text-base font-bold text-slate-800">Mapa de Levantamentos</h2>
          </div>
          {isMapOpen ? <ChevronUp size={20} className="text-slate-400" /> : <ChevronDown size={20} className="text-slate-400" />}
        </button>

        {isMapOpen && (
          <div className="border-t border-slate-100">
            <div className="flex items-center justify-between px-4 pt-3 pb-2">
              <div className="text-xs font-semibold text-slate-500">Visão Geral no Mapa</div>
              <button
                onClick={() => setIsFullscreenMap(true)}
                className="text-xs font-bold text-brand-blue hover:underline flex items-center gap-1"
              >
                Expandir
              </button>
            </div>
            {/* Legend */}
            <div className="flex items-center gap-4 px-4 pb-3">
              <div className="flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-full bg-blue-500" /><span className="text-xs text-slate-600">Normal</span></div>
              <div className="flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-full bg-orange-500" /><span className="text-xs text-slate-600">Suspeita</span></div>
              <div className="flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-full bg-red-600" /><span className="text-xs text-slate-600">Foco</span></div>
            </div>
            {/* Mapa Leaflet — ocupa todo o espaço disponível */}
            <div className="h-80 w-full relative z-0">
              <InteractiveMap
                data={dashboardData.mapa_pontos}
                isFullscreen={false}
                onToggleFullscreen={() => setIsFullscreenMap(true)}
              />
            </div>
          </div>
        )}
      </section>

      {/* ── Tabs: Programas / Propriedades / Produtores ── */}
      <section className="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        {/* Tab pills */}
        <div className="flex gap-2 p-3 border-b border-slate-100 overflow-x-auto">
          {['programas', 'propriedades', 'produtores'].map(tab => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={clsx(
                'px-4 py-1.5 rounded-full text-sm font-semibold whitespace-nowrap transition-colors',
                activeTab === tab
                  ? 'bg-brand-blue/10 text-brand-blue'
                  : 'text-slate-500 hover:bg-slate-100'
              )}
            >
              {tab.charAt(0).toUpperCase() + tab.slice(1)}
            </button>
          ))}
        </div>

        {/* List title */}
        <div className="px-4 pt-4 pb-2">
          <h3 className="text-sm font-bold text-brand-blue">{tabLabels[activeTab]}</h3>
        </div>

        {/* Items */}
        <div className="flex flex-col divide-y divide-slate-50">
          {tabItems[activeTab].length === 0 ? (
            <p className="px-4 py-6 text-center text-sm text-slate-400">Nenhum registro encontrado.</p>
          ) : (
            tabItems[activeTab].slice(0, 5).map((item, idx) => (
              <div key={idx} className="flex items-center justify-between px-4 py-3">
                <div className="min-w-0 flex-1 mr-3">
                  <p className="text-sm font-semibold text-slate-800 truncate">{item.primary}</p>
                  <p className="text-xs text-slate-400 truncate">{item.secondary}</p>
                </div>
                {item.secondary && (
                  <span className="shrink-0 px-2 py-0.5 rounded-lg bg-brand-blue/10 text-brand-blue text-[10px] font-bold uppercase max-w-[90px] truncate">
                    {item.secondary?.substring(0, 12)}
                  </span>
                )}
                <ChevronRight size={16} className="shrink-0 text-slate-300 ml-2" />
              </div>
            ))
          )}
        </div>

        {/* Ver Todos */}
        <div className="p-3">
          <button
            onClick={() => setCurrentRoute(tabRoutes[activeTab])}
            className="w-full py-2.5 rounded-xl text-sm font-bold text-slate-600 border border-slate-200 hover:bg-slate-50 transition-colors"
          >
            Ver Todos
          </button>
        </div>
      </section>
    </div>
  );
}

/* ─────────────────────────────────────────────
   MAIN DASHBOARD COMPONENT
───────────────────────────────────────────── */
function Dashboard() {
  const [user, setUser] = useState(null);
  const [authReady, setAuthReady] = useState(false);
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [isFullscreenMap, setIsFullscreenMap] = useState(false);
  const [currentRoute, setCurrentRoute] = useState('dashboard');
  const [isOnline, setIsOnline] = useState(navigator.onLine);
  const [lastSync, setLastSync] = useState(null);

  useLayoutEffect(() => {
    try {
      const stored = localStorage.getItem('user');
      if (stored) {
        const parsed = JSON.parse(stored);
        if (parsed && typeof parsed === 'object' && parsed.id) {
          setUser(parsed);
        }
      }
    } catch (e) {
      // localStorage corrompido — ignora e exibe login
    } finally {
      setAuthReady(true);
    }
  }, []);

  const cachedData = useLiveQuery(() => db.dashboardCache.get('latest'));

  const defaultData = {
    stats: { total_programas: 0, total_municipios: 0, count_normal: 0, count_suspeita: 0, count_foco: 0 },
    mapa_pontos: [],
    recent_programas: [],
    recent_propriedades: [],
    recent_produtores: []
  };

  const dashboardData = cachedData?.data || defaultData;

  const syncDashboard = async () => {
    try {
      const API_URL = import.meta.env.VITE_API_URL || 'https://svegpa-api.vps5908.panel.icontainer.run/api';
      const res = await axios.get(`${API_URL}/dashboard/`);
      await db.dashboardCache.put({ id: 'latest', data: res.data, updatedAt: new Date().toISOString() });
      setLastSync(new Date());
    } catch (err) {
      console.error("Erro na sincronização do dashboard:", err);
    }
  };

  useEffect(() => {
    const handleOnline = () => { setIsOnline(true); syncDashboard(); };
    const handleOffline = () => setIsOnline(false);
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  useEffect(() => {
    if (authReady && user && navigator.onLine) {
      syncDashboard();
    }
  }, [authReady, user]);

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setUser(null);
  };

  // Título da rota atual para o header mobile
  const routeTitles = {
    dashboard: 'Dashboard',
    programas: 'Programas',
    hospedeiros: 'Hospedeiros',
    normas: 'Normas',
    propriedades: 'Propriedades',
    produtores: 'Produtores',
    inspecao: 'Inspeção',
    amostragem: 'Amostragem',
    usuarios: 'Usuários',
    orgaos: 'Órgãos',
    unidades: 'Unidades',
    cargos: 'Cargos',
    tipos_orgao: 'Tipos de Órgão',
    historico_levantamentos: 'Histórico',
    perfil: 'Perfil',
  };

  if (!authReady) return null;

  if (!user) {
    return <Login onLoginSuccess={(u) => {
      setUser(u);
      if (navigator.onLine) syncDashboard();
    }} />;
  }

  // Overlay de fundo quando a sidebar estiver aberta no mobile
  const sidebarOverlay = isSidebarOpen && (
    <div
      className="md:hidden fixed inset-0 z-40 bg-black/40 backdrop-blur-sm"
      onClick={() => setIsSidebarOpen(false)}
    />
  );

  return (
    <div className="min-h-screen flex bg-slate-50 font-sans text-slate-800">

      {/* ─── Mobile Top Header ─── */}
      <MobileHeader
        onMenuClick={() => setIsSidebarOpen(true)}
        onProfileClick={() => setCurrentRoute('perfil')}
        title={routeTitles[currentRoute] || 'Dashboard'}
        subtitle={currentRoute === 'dashboard' ? 'Inspeções Fitossanitárias' : 'Sanidade Vegetal'}
      />

      {/* ─── Sidebar Overlay (mobile) ─── */}
      {sidebarOverlay}

      {/* ─── Sidebar ─── */}
      <aside
        className={clsx(
          "glass fixed inset-y-0 left-0 z-50 transform transition-all duration-300 ease-in-out flex-shrink-0 h-screen flex flex-col justify-between border-r border-slate-200/50 overflow-hidden",
          // Mobile: w-64; Desktop: toggles w-20 / w-64
          isCollapsed ? "md:w-20 w-64" : "w-64",
          isSidebarOpen ? "translate-x-0" : "-translate-x-full",
          "md:relative md:translate-x-0 md:flex"
        )}
      >
        <div className="flex flex-col h-full">
          {/* Logo & Close/Toggle */}
          <div className={clsx("p-6 flex items-center justify-between", isCollapsed && "md:p-4 md:justify-center")}>
            <div className="flex items-center gap-3 overflow-hidden">
              <div className="bg-brand-blue p-2 rounded-lg text-white shrink-0">
                <Leaf size={24} />
              </div>
              {!isCollapsed && <span className="text-xl font-bold tracking-tight text-slate-800 whitespace-nowrap">Sanveg PA</span>}
            </div>

            {/* Mobile: X Button */}
            <button className="md:hidden text-slate-500 hover:text-slate-800 transition-colors" onClick={() => setIsSidebarOpen(false)}>
              <X size={24} />
            </button>

            {/* Desktop: Collapse Toggle */}
            {!isSidebarOpen && (
              <button
                className="hidden md:flex text-slate-500 hover:text-brand-blue transition-colors p-1 hover:bg-slate-100 rounded-md"
                onClick={() => setIsCollapsed(!isCollapsed)}
                title={isCollapsed ? "Expandir Menu" : "Recolher Menu"}
              >
                {isCollapsed ? <ChevronRight size={20} /> : <ChevronLeft size={20} />}
              </button>
            )}
          </div>

          {/* Navigation */}
          <nav className={clsx("flex-1 space-y-1 mt-4 overflow-y-auto pb-4 px-4", isCollapsed && "md:px-2")}>
            <NavGroup icon={<PieChart size={20} />} label="DASHBOARDS" initOpen={true} isCollapsed={isCollapsed} onExpand={() => setIsCollapsed(false)}>
              <NavItem icon={<Home size={18} />} label="Principal" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('dashboard'); setIsSidebarOpen(false); }} active={currentRoute === 'dashboard'} />
              <NavItem icon={<Map size={18} />} label="Outros" isCollapsed={isCollapsed} />
            </NavGroup>

            <NavGroup icon={<Database size={20} />} label="CADASTRO" isCollapsed={isCollapsed} onExpand={() => setIsCollapsed(false)}>
              <NavGroup icon={<Folder size={18} />} label="PROGRAMAS" isCollapsed={isCollapsed} onExpand={() => setIsCollapsed(false)}>
                <NavItem icon={<FileText size={16} />} label="Programas" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('programas'); setIsSidebarOpen(false); }} active={currentRoute === 'programas'} />
                <NavItem icon={<Leaf size={16} />} label="Hospedeiros" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('hospedeiros'); setIsSidebarOpen(false); }} active={currentRoute === 'hospedeiros'} />
                <NavItem icon={<BookOpen size={16} />} label="Normas" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('normas'); setIsSidebarOpen(false); }} active={currentRoute === 'normas'} />
              </NavGroup>
              <NavGroup icon={<Settings size={18} />} label="AUXILIARES" isCollapsed={isCollapsed} onExpand={() => setIsCollapsed(false)}>
                <NavItem icon={<Users size={16} />} label="Usuários" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('usuarios'); setIsSidebarOpen(false); }} active={currentRoute === 'usuarios'} />
                <NavItem icon={<Bookmark size={16} />} label="Tipos de Órgão" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('tipos_orgao'); setIsSidebarOpen(false); }} active={currentRoute === 'tipos_orgao'} />
                <NavItem icon={<Building size={16} />} label="Órgãos" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('orgaos'); setIsSidebarOpen(false); }} active={currentRoute === 'orgaos'} />
                <NavItem icon={<MapPin size={16} />} label="Unidades" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('unidades'); setIsSidebarOpen(false); }} active={currentRoute === 'unidades'} />
                <NavItem icon={<Briefcase size={16} />} label="Cargos" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('cargos'); setIsSidebarOpen(false); }} active={currentRoute === 'cargos'} />
              </NavGroup>
            </NavGroup>

            <NavGroup icon={<Activity size={20} />} label="LEVANTAMENTOS" isCollapsed={isCollapsed} onExpand={() => setIsCollapsed(false)}>
              <NavItem icon={<Users size={18} />} label="Produtores" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('produtores'); setIsSidebarOpen(false); }} active={currentRoute === 'produtores'} />
              <NavItem icon={<Home size={18} />} label="Propriedades" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('propriedades'); setIsSidebarOpen(false); }} active={currentRoute === 'propriedades'} />
              <NavItem icon={<FileText size={18} />} label="Inspeção" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('inspecao'); setIsSidebarOpen(false); }} active={currentRoute === 'inspecao'} />
              <NavItem icon={<CheckCircle size={18} />} label="Amostragem" isCollapsed={isCollapsed} onClick={() => { setCurrentRoute('amostragem'); setIsSidebarOpen(false); }} active={currentRoute === 'amostragem'} />
            </NavGroup>

            <NavGroup icon={<ClipboardList size={20} />} label="RELATÓRIOS" isCollapsed={isCollapsed} onExpand={() => setIsCollapsed(false)}>
              <NavItem icon={<FileText size={18} />} label="Gerais" isCollapsed={isCollapsed} />
              <NavItem
                icon={<Search size={18} />}
                label="Histórico de Levantamentos"
                isCollapsed={isCollapsed}
                onClick={() => { setCurrentRoute('historico_levantamentos'); setIsSidebarOpen(false); }}
                active={currentRoute === 'historico_levantamentos'}
              />
            </NavGroup>

            <div className="pt-2 mt-2 border-t border-slate-200/50 space-y-1">
              <NavItem icon={<User size={20} />} label="Minha Conta" isCollapsed={isCollapsed} />
            </div>
          </nav>

          {/* User Profile & Logout */}
          <div className={clsx(
            "m-4 bg-white/50 rounded-xl border border-slate-200/50 backdrop-blur-md flex items-center justify-between transition-all",
            isCollapsed ? "p-2 mb-4 bg-transparent border-transparent flex-col gap-4" : "p-4"
          )}>
            <div className={clsx("flex items-center gap-3 min-w-0", isCollapsed && "flex-col")}>
              <div className="w-10 h-10 shrink-0 rounded-full bg-brand-blue-light flex items-center justify-center text-white font-bold uppercase ring-2 ring-white shadow-sm">
                {(user?.nome || 'US').substring(0, 2)}
              </div>
              {!isCollapsed && (
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-semibold text-slate-800 truncate">{user?.nome || 'Usuário'}</p>
                  <p className="text-xs text-slate-500 truncate">{user?.role || ''}</p>
                </div>
              )}
            </div>
            <button onClick={handleLogout} className={clsx("shrink-0 text-slate-500 hover:text-red-500 transition-colors p-2 rounded-lg hover:bg-white", !isCollapsed && "ml-2")} title="Sair">
              <LogOut size={20} />
            </button>
          </div>
        </div>
      </aside>

      {/* ─── Main Content Area ─── */}
      <main className={clsx(
        'flex-1 flex flex-col min-h-screen overflow-x-hidden',
        // Mobile: padding top para o header fixo, padding bottom para o bottom nav
        'pt-14 pb-16 px-4 md:pt-0 md:pb-0 md:px-0',
        'md:p-4 lg:p-8',
        isFullscreenMap ? 'h-screen overflow-y-hidden' : ''
      )}>

        {/* Global Header — Desktop only */}
        <header className={clsx("hidden md:flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4", isFullscreenMap ? "mb-2 md:mb-4" : "mb-4")}>
          <div>
            <h1 className="text-xl lg:text-2xl font-extrabold text-slate-800 tracking-tight">
              {currentRoute === 'dashboard' ? 'Dashboard - Sanidade Vegetal' : 'Operação Local'}
            </h1>
            <p className="text-slate-500 font-medium text-sm">
              {currentRoute === 'dashboard' ? 'Resumo operacional e monitoramento fitossanitário' : 'Gestão Offline-First'}
            </p>
          </div>
          <div>
            {!isFullscreenMap && <SyncManager onSyncComplete={syncDashboard} />}
          </div>
        </header>

        {/* ─── DASHBOARD ROUTE ─── */}
        {currentRoute === 'dashboard' && (
          <>
            {/* MOBILE dashboard layout */}
            <div className="md:hidden">
              {isFullscreenMap ? (
                <div className="fixed inset-0 z-20 pt-14 flex flex-col bg-white">
                  <InteractiveMap
                    data={dashboardData.mapa_pontos}
                    isFullscreen={true}
                    onToggleFullscreen={() => setIsFullscreenMap(false)}
                  />
                </div>
              ) : (
                <MobileDashboard
                  dashboardData={dashboardData}
                  setCurrentRoute={setCurrentRoute}
                  isFullscreenMap={isFullscreenMap}
                  setIsFullscreenMap={setIsFullscreenMap}
                  syncDashboard={syncDashboard}
                />
              )}
            </div>

            {/* DESKTOP dashboard layout — unchanged */}
            <div className="hidden md:contents">
              {!isFullscreenMap && (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 lg:gap-6 mb-6">
                  <StatCard title="Programas" value={dashboardData.stats.total_programas} icon={<FileText size={24} className="text-brand-blue" />} bgClass="bg-brand-blue/10" />
                  <StatCard title="Municípios" value={dashboardData.stats.total_municipios} icon={<MapPin size={24} className="text-blue-500" />} bgClass="bg-blue-50" />
                  <StatCard title="Normal" value={dashboardData.stats.count_normal} tooltip="Levantamentos Sem Foco" icon={<CheckCircle size={24} className="text-slate-500" />} bgClass="bg-slate-100" />
                  <StatCard title="Suspeita" value={dashboardData.stats.count_suspeita} icon={<AlertTriangle size={24} className="text-suspect-orange" />} bgClass="bg-orange-50" textColor="text-suspect-orange" />
                  <StatCard title="Foco Detectado" value={dashboardData.stats.count_foco} icon={<AlertCircle size={24} className="text-outbreak-red" />} bgClass="bg-red-50" textColor="text-outbreak-red" />
                </div>
              )}

              <div className={clsx("flex flex-col w-full", !isFullscreenMap ? "mb-6 h-[400px] sm:h-[450px] lg:h-[500px] shrink-0" : "flex-1 min-h-0 h-full mb-0")}>
                <InteractiveMap
                  data={dashboardData.mapa_pontos}
                  isFullscreen={isFullscreenMap}
                  onToggleFullscreen={() => setIsFullscreenMap(!isFullscreenMap)}
                />
              </div>

              {!isFullscreenMap && (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                  <ListCard title="Programas Recentes" icon={<FileText size={20} className="text-brand-blue" />} items={dashboardData.recent_programas} />
                  <ListCard title="Propriedades Recentes" icon={<Home size={20} className="text-emerald-600" />} items={dashboardData.recent_propriedades} />
                  <ListCard title="Produtores Recentes" icon={<Users size={20} className="text-teal-600" />} items={dashboardData.recent_produtores} />
                </div>
              )}
            </div>
          </>
        )}

        {currentRoute === 'programas' && <ProgramasCRUD user={user} />}
        {currentRoute === 'hospedeiros' && <HospedeirosCRUD user={user} />}
        {currentRoute === 'normas' && <NormasCRUD user={user} />}
        {currentRoute === 'propriedades' && <PropriedadesCRUD user={user} />}
        {currentRoute === 'produtores' && <ProdutoresCRUD user={user} />}
        {currentRoute === 'tipos_orgao' && <TiposOrgaoCRUD user={user} />}
        {currentRoute === 'orgaos' && <OrgaosCRUD user={user} />}
        {currentRoute === 'unidades' && <UnidadesCRUD user={user} />}
        {currentRoute === 'cargos' && <CargosCRUD user={user} />}
        {currentRoute === 'usuarios' && <UsuariosCRUD user={user} />}
        {currentRoute === 'inspecao' && <InspecaoCRUD user={user} />}
        {currentRoute === 'amostragem' && <AmostragemCRUD />}
        {currentRoute === 'historico_levantamentos' && <HistoricoLevantamentos user={user} />}

        {/* Perfil — tela simples mobile */}
        {currentRoute === 'perfil' && (
          <div className="flex flex-col items-center gap-6 py-8">
            <div className="w-20 h-20 rounded-full bg-brand-blue flex items-center justify-center text-white text-3xl font-bold uppercase">
              {(user?.nome || 'US').substring(0, 2)}
            </div>
            <div className="text-center">
              <p className="text-xl font-bold text-slate-800">{user?.nome}</p>
              <p className="text-sm text-slate-400 capitalize">{user?.role}</p>
            </div>
            <button
              onClick={handleLogout}
              className="flex items-center gap-2 px-6 py-3 bg-red-50 text-red-600 font-bold rounded-xl border border-red-100 hover:bg-red-100 transition-colors"
            >
              <LogOut size={18} /> Sair da conta
            </button>
          </div>
        )}

      </main>

      {/* ─── Mobile Bottom Nav ─── */}
      <MobileBottomNav currentRoute={currentRoute} setCurrentRoute={setCurrentRoute} />

    </div>
  );
}

/* ─────────────────────────────────────────────
   SHARED HELPER COMPONENTS
───────────────────────────────────────────── */

function NavGroup({ icon, label, children, initOpen = false, isCollapsed, onExpand }) {
  const [isOpen, setIsOpen] = useState(initOpen);

  const handleToggle = () => {
    if (isCollapsed && onExpand) {
      onExpand();
      setIsOpen(true);
    } else {
      setIsOpen(!isOpen);
    }
  };

  return (
    <div className="flex flex-col mb-1">
      <button
        onClick={handleToggle}
        className={clsx(
          "flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 w-full focus:outline-none",
          isOpen && !isCollapsed ? "text-brand-blue bg-brand-blue/10 font-bold" : "text-slate-600 hover:bg-white hover:text-slate-900 font-semibold",
          isCollapsed && "md:px-0 md:justify-center"
        )}
        title={isCollapsed ? label : undefined}
      >
        <div className="flex items-center gap-3">
          <div className={clsx(isOpen && !isCollapsed ? "text-brand-blue" : "text-slate-500")}>{icon}</div>
          {!isCollapsed && <span className="text-sm uppercase tracking-wider whitespace-nowrap overflow-hidden">{label}</span>}
        </div>
        {!isCollapsed && (isOpen ? <ChevronUp size={16} className="text-slate-400" /> : <ChevronDown size={16} className="text-slate-400" />)}
      </button>
      <div className={clsx("overflow-hidden transition-all duration-300 ease-in-out", (isOpen && !isCollapsed) ? "max-h-[800px] opacity-100 mt-1" : "max-h-0 opacity-0")}>
        <div className={clsx("pr-1 py-1 space-y-1 ml-[1.125rem] border-l-2 border-slate-100/70", isCollapsed ? "md:hidden" : "pl-3")}>
          {children}
        </div>
      </div>
    </div>
  );
}

function NavItem({ icon, label, active = false, onClick, isCollapsed }) {
  return (
    <a href="#" onClick={(e) => { e.preventDefault(); if (onClick) onClick(); }} className={clsx(
      "flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200",
      active ? "bg-brand-blue text-white shadow-md shadow-brand-blue/20" : "text-slate-600 hover:bg-white hover:text-slate-900",
      isCollapsed && "md:px-0 md:justify-center"
    )} title={isCollapsed ? label : undefined}>
      <div className={clsx(active ? "text-white" : "text-slate-400")}>{icon}</div>
      {!isCollapsed && <span className="whitespace-nowrap overflow-hidden">{label}</span>}
    </a>
  );
}

function StatCard({ title, value, icon, bgClass, textColor = "text-slate-800", tooltip }) {
  return (
    <div className="bg-white p-5 rounded-2xl card-shadow border border-slate-100 flex flex-col justify-between hover:shadow-lg transition-shadow cursor-default group" title={tooltip}>
      <div className="flex justify-between items-start mb-4">
        <div className={clsx("p-3 rounded-xl", bgClass)}>{icon}</div>
      </div>
      <div>
        <p className="text-slate-500 font-medium text-sm mb-1">{title}</p>
        <h3 className={clsx("text-3xl font-extrabold tracking-tight", textColor)}>{value}</h3>
      </div>
    </div>
  );
}

function ListCard({ title, icon, items }) {
  return (
    <div className="bg-white rounded-2xl card-shadow border border-slate-100 p-5 flex flex-col">
      <h3 className="text-lg font-bold flex items-center gap-2 mb-4 text-slate-800 border-b border-slate-100 pb-3">
        {icon} {title}
      </h3>
      <div className="flex flex-col gap-3">
        {items.map((item, idx) => (
          <div key={idx} className="group p-3 rounded-xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100 cursor-pointer">
            <p className="font-bold text-slate-800 group-hover:text-brand-blue transition-colors">{item.primary}</p>
            <p className="text-sm text-slate-500 font-medium">{item.secondary}</p>
          </div>
        ))}
      </div>
      <button className="mt-4 w-full py-2.5 rounded-xl text-sm font-bold text-brand-blue bg-brand-blue/10 hover:bg-brand-blue/20 transition-colors">
        Ver Todos
      </button>
    </div>
  );
}

export default Dashboard;
