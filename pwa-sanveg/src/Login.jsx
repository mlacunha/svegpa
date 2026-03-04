import React, { useState } from 'react';
import { Leaf, Lock, Mail, AlertCircle, Loader } from 'lucide-react';
import axios from 'axios';
import clsx from 'clsx';
import { db } from './db';

const API_URL = import.meta.env.VITE_API_URL || 'https://svegpa-api.vps5908.panel.icontainer.run/api';

export default function Login({ onLoginSuccess }) {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(false);

    const handleLogin = async (e) => {
        e.preventDefault();
        if (!username || !password) {
            setError('Por favor, preencha todos os campos.');
            return;
        }

        setError('');
        setIsLoading(true);

        try {
            const params = new URLSearchParams();
            params.append('username', username); // FastAPI OAuth2 requires 'username' and 'password' URL encoded fields
            params.append('password', password);

            const response = await axios.post(`${API_URL}/auth/token`, params, {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });

            const { access_token, user } = response.data;

            // Store in localStorage
            localStorage.setItem('token', access_token);
            localStorage.setItem('user', JSON.stringify(user));

            // Re-trigger auth state in App
            onLoginSuccess(user);

        } catch (err) {
            if (!navigator.onLine) {
                // TODO: Implement Offline Login verification against local Dexie 'usuarios' table if hashed or PIN based.
                // For now, require internet for initial log-in, then hold session.
                setError('Você precisa estar com internet para fazer o primeiro login e iniciar uma sessão offline.');
            } else if (err.response && err.response.data && err.response.data.detail) {
                setError(err.response.data.detail);
            } else {
                setError('Ocorreu um erro ao conectar com o servidor.');
            }
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col justify-center items-center p-4">
            <div className="max-w-md w-full bg-white rounded-3xl card-shadow border border-slate-100 p-8">
                <div className="flex flex-col items-center mb-8">
                    <div className="w-16 h-16 bg-brand-blue rounded-2xl flex items-center justify-center text-white mb-4 shadow-lg shadow-brand-blue/30">
                        <Leaf size={32} />
                    </div>
                    <h1 className="text-2xl font-extrabold text-slate-800">Sanveg PA</h1>
                    <p className="text-slate-500 font-medium mt-1">Insira suas credenciais para continuar</p>
                </div>

                {error && (
                    <div className="mb-6 bg-red-50 text-outbreak-red px-4 py-3 rounded-xl flex items-start gap-3 text-sm font-semibold border border-red-100">
                        <AlertCircle size={18} className="mt-0.5 shrink-0" />
                        <p>{error}</p>
                    </div>
                )}

                <form onSubmit={handleLogin} className="space-y-5">
                    <div className="space-y-1.5">
                        <label className="text-sm font-bold text-slate-700 ml-1">CPF ou E-mail</label>
                        <div className="relative">
                            <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
                            <input
                                type="text"
                                placeholder="nome@instituicao.gov.br ou 000.000.000-00"
                                value={username}
                                onChange={(e) => setUsername(e.target.value)}
                                className="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 transition-all font-medium text-slate-800 bg-slate-50 focus:bg-white"
                            />
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <label className="text-sm font-bold text-slate-700 ml-1">Senha</label>
                        <div className="relative">
                            <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
                            <input
                                type="password"
                                placeholder="••••••••"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                className="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 transition-all font-medium text-slate-800 bg-slate-50 focus:bg-white"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={isLoading}
                        className={clsx(
                            "w-full flex items-center justify-center gap-2 py-3.5 rounded-xl font-bold text-white transition-all shadow-lg",
                            isLoading ? "bg-brand-blue-light cursor-not-allowed shadow-none" : "bg-brand-blue hover:bg-brand-blue-light hover:shadow-brand-blue/30 hover:-translate-y-0.5 active:translate-y-0"
                        )}
                    >
                        {isLoading ? <Loader className="animate-spin" size={20} /> : 'Acessar Plataforma'}
                    </button>
                </form>
            </div>

            <div className="mt-8 text-slate-400 text-sm font-medium">
                &copy; {new Date().getFullYear()} - SIFISV/DDA/SFA-PA
            </div>
        </div>
    );
}
