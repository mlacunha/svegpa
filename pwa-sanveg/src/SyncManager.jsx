import React, { useState, useEffect } from 'react';
import { Cloud, CloudOff, RefreshCw, AlertCircle, CheckCircle, Wifi, WifiOff } from 'lucide-react';
import clsx from 'clsx';
import { db } from './db';
import axios from 'axios';
import { useLiveQuery } from 'dexie-react-hooks';

// Ajuste para o IP local quando rodar com servidor FastAPI localmente
const API_URL = import.meta.env.VITE_API_URL || 'https://svegpa-api.vps5908.panel.icontainer.run/api';

export default function SyncManager({ onSyncComplete }) {
    const [isOnline, setIsOnline] = useState(navigator.onLine);
    const [isSyncing, setIsSyncing] = useState(false);
    const [lastSync, setLastSync] = useState(localStorage.getItem('lastSync') || null);

    // Watch sync queue
    const syncQueue = useLiveQuery(() => db.syncQueue.toArray()) || [];

    useEffect(() => {
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    // Try background sync every 30 seconds if online
    useEffect(() => {
        let interval;
        if (isOnline) {
            interval = setInterval(() => {
                handleSync();
            }, 30000);
        }
        return () => clearInterval(interval);
    }, [isOnline, syncQueue.length]);

    const handleSync = async () => {
        if (!isOnline || isSyncing) return;
        setIsSyncing(true);

        try {
            // 1. DUMP QUEUE / PUSH
            const queueItems = await db.syncQueue.toArray();

            if (queueItems.length > 0) {
                const pushResponse = await axios.post(`${API_URL}/sync/push`, {
                    queue: queueItems
                });

                // Clear processed items from dexie
                // API returns { processed_ids: [1, 2, 3...] }
                if (pushResponse.data && pushResponse.data.processed_ids) {
                    await db.syncQueue.bulkDelete(pushResponse.data.processed_ids);
                }

                // Show errors if any failed permanently on the server side
                if (pushResponse.data && pushResponse.data.errors && pushResponse.data.errors.length > 0) {
                    console.error("🔴 ERROS DE PUSH REPORTADOS PELA API:", pushResponse.data.errors);
                    const errorMsgs = pushResponse.data.errors.map(e => e.error).join('\n\n');
                    alert(`Não foi possível enviar ${pushResponse.data.errors.length} registros para o servidor.\n\nMotivo do Banco de Dados / API:\n${errorMsgs}\n\nAbra o Console (F12) para mais detalhes do erro.`);
                }
            }

            // 2. FETCH LATEST / PULL
            const pullResponse = await axios.get(`${API_URL}/sync/pull`);
            if (pullResponse.data) {
                // Bulk put is very fast. Overwrites local objects with server's object
                if (pullResponse.data.programas?.length > 0) await db.programas.bulkPut(pullResponse.data.programas);
                if (pullResponse.data.propriedades?.length > 0) await db.propriedades.bulkPut(pullResponse.data.propriedades);
                if (pullResponse.data.produtores?.length > 0) await db.produtores.bulkPut(pullResponse.data.produtores);
                if (pullResponse.data.termo_inspecao?.length > 0) await db.termo_inspecao.bulkPut(pullResponse.data.termo_inspecao);
                if (pullResponse.data.area_inspecionada?.length > 0) await db.area_inspecionada.bulkPut(pullResponse.data.area_inspecionada);

                // Auxiliares
                if (pullResponse.data.hospedeiros?.length > 0) await db.hospedeiros.bulkPut(pullResponse.data.hospedeiros);
                if (pullResponse.data.normas?.length > 0) await db.normas.bulkPut(pullResponse.data.normas);
                if (pullResponse.data.tipos_orgao?.length > 0) await db.tipos_orgao.bulkPut(pullResponse.data.tipos_orgao);
                if (pullResponse.data.orgaos?.length > 0) await db.orgaos.bulkPut(pullResponse.data.orgaos);
                if (pullResponse.data.unidades?.length > 0) await db.unidades.bulkPut(pullResponse.data.unidades);
                if (pullResponse.data.cargos?.length > 0) await db.cargos.bulkPut(pullResponse.data.cargos);
                if (pullResponse.data.formacoes?.length > 0) await db.formacoes.bulkPut(pullResponse.data.formacoes);
                if (pullResponse.data.usuarios?.length > 0) await db.usuarios.bulkPut(pullResponse.data.usuarios);
            }

            const now = new Date().toLocaleTimeString();
            setLastSync(now);
            localStorage.setItem('lastSync', now);

            // Notifica o Dashboard para recarregar os dados após o sync
            if (onSyncComplete) onSyncComplete();

        } catch (error) {
            console.error('Sync failed:', error);
        } finally {
            setIsSyncing(false);
        }
    };

    return (
        <div className="flex items-center gap-4 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100">
            <div className="flex items-center gap-2">
                {isOnline ? (
                    <span className="flex items-center gap-1.5 text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md">
                        <Wifi size={14} /> <span className="hidden md:inline">ONLINE</span>
                    </span>
                ) : (
                    <span className="flex items-center gap-1.5 text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-md">
                        <WifiOff size={14} /> <span className="hidden md:inline">OFFLINE</span>
                    </span>
                )}
            </div>

            <div className="h-6 w-px bg-slate-200"></div>

            <div className="flex items-center gap-3">
                <button
                    onClick={handleSync}
                    disabled={!isOnline || isSyncing}
                    className={clsx(
                        "flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg transition-all",
                        isSyncing || !isOnline
                            ? "bg-slate-100 text-slate-400 cursor-not-allowed"
                            : "bg-brand-blue/10 text-brand-blue hover:bg-brand-blue/20"
                    )}
                >
                    <RefreshCw size={14} className={isSyncing ? "animate-spin" : ""} />
                    <span className="hidden md:inline">{isSyncing ? 'Sincronizando...' : 'Sincronizar Agora'}</span>
                </button>
                <div className="hidden md:flex flex-col">
                    <span className="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Última Sincronização</span>
                    <span className="text-xs text-slate-700 font-medium flex items-center gap-1">
                        {lastSync ? <><CheckCircle size={10} className="text-emerald-500" /> {lastSync}</> : 'Aguardando...'}
                    </span>
                </div>
            </div>

            <div className="h-6 w-px bg-slate-200"></div>

            <div className="flex flex-col items-center justify-center px-1 md:px-2">
                <span className="hidden md:block text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Fila Offline</span>
                <span className={clsx("text-xs font-bold", syncQueue.length > 0 ? "text-amber-500" : "text-emerald-500")}>
                    {syncQueue.length} Registros
                </span>
            </div>
        </div>
    );
}
