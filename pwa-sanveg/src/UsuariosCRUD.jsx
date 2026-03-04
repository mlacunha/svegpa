import React, { useState, useEffect } from 'react';
import { Plus, Edit2, Trash2, Search, ArrowLeft, Save, Users, Shield, UserCircle, Building } from 'lucide-react';
import { useLiveQuery } from 'dexie-react-hooks';
import { db, offlineSave, offlineDelete } from './db';

export default function UsuariosCRUD({ user }) {
    const mockLoggedInUser = user || { role: 'comum', id_orgao: '' }; // fallback to the authenticated user from App.jsx
    const [view, setView] = useState('list');
    const [searchTerm, setSearchTerm] = useState('');

    const usuarios = useLiveQuery(() => db.usuarios.toArray()) || [];
    const orgaos = useLiveQuery(() => db.orgaos.toArray()) || [];
    const unidades = useLiveQuery(() => db.unidades.toArray()) || [];
    const cargos = useLiveQuery(() => db.cargos.toArray()) || [];
    const formacoes = useLiveQuery(() => db.formacoes.toArray()) || [];

    const [currentUser, setCurrentUser] = useState(null);

    // Seed initial Superuser
    useEffect(() => {
        const seedSuperUser = async () => {
            const count = await db.usuarios.where({ role: 'superusuario' }).count();
            if (count === 0) {
                await db.usuarios.add({
                    id: 'super-admin-001',
                    nome: 'Admin Master',
                    email: 'super@sanveg.com',
                    cpf: '000.000.000-00',
                    role: 'superusuario',
                    id_orgao: '',
                    id_unidade: '',
                    id_cargo: '',
                    ativo: true
                });
            }
        };
        seedSuperUser();
    }, []);

    // Filter users list by search and RBAC rules
    const filteredUsuarios = usuarios.filter(u => {
        // Enforce RBAC filtering for list view
        if (mockLoggedInUser.role === 'admin') {
            if (u.id_orgao !== mockLoggedInUser.id_orgao) return false;
            if (u.role === 'superusuario') return false; // Admin cannot see superusuarios
        } else if (mockLoggedInUser.role === 'comum') {
            if (u.id !== mockLoggedInUser.id) return false;
        }

        return (
            u.nome.toLowerCase().includes(searchTerm.toLowerCase()) ||
            u.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
            (u.cpf && u.cpf.includes(searchTerm))
        );
    });

    const handleEdit = (u) => {
        if (mockLoggedInUser.role === 'admin' && u.role === 'superusuario') {
            alert('Você não tem permissão para editar um Superusuário.');
            return;
        }
        setCurrentUser({ ...u });
        setView('form');
    };

    const handleCreate = () => {
        setCurrentUser({
            id: crypto.randomUUID(),
            nome: '',
            email: '',
            telefone: '',
            matricula: '',
            carteirafiscal: '',
            cpf: '',
            senha: '', // Senhas no mundo offline costumam ser mockadas ou placeholder
            role: 'comum', // 'comum', 'admin', 'superusuario'
            id_orgao: mockLoggedInUser.role === 'admin' ? mockLoggedInUser.id_orgao : '',
            id_unidade: '',
            id_cargo: '',
            id_formacao: '',
            ativo: true
        });
        setView('form');
    };

    const handleDelete = async (user) => {
        if (mockLoggedInUser.role === 'admin' && user.role === 'superusuario') {
            alert('Acesso negado para excluir um Superusuário.');
            return;
        }
        if (window.confirm("Deseja realmente excluir ou inativar este Usuário?")) {
            await offlineDelete('usuarios', user.id);
        }
    };

    const handleSave = async () => {
        if (!currentUser.nome || !currentUser.email) {
            alert('Nome e Email são obrigatórios!');
            return;
        }

        // Enforcement for Admins attempting privilege escalation
        if (mockLoggedInUser.role === 'admin') {
            if (currentUser.role === 'superusuario') {
                alert('Acesso negado ao tentar criar/aplicar papel de Superusuário.');
                return;
            }
            if (currentUser.id_orgao !== mockLoggedInUser.id_orgao) {
                alert('Você só pode criar usuários para sua própria instituição.');
                return;
            }
        }

        await offlineSave('usuarios', currentUser);
        setView('list');
    };

    const getOrgaoNome = (id_orgao) => {
        if (!id_orgao) return 'Administração Geral';
        const o = orgaos.find(org => org.id === id_orgao);
        return o ? (o.sigla || o.nome) : 'Não informado';
    };

    const getFormacaoNome = (id_formacao) => {
        if (!id_formacao) return '-';
        // Formação is usually numeric ID from standard API syncs, we use loose comparison
        const f = formacoes.find(form => form.id == id_formacao);
        return f ? f.nome : 'Desconhecida';
    };

    // Comum profile editing allowed over their own record, hiding the Restrito message

    // Role-based Dropdowns options
    const roleOptions = [
        { value: 'comum', label: 'Usuário Comum (Campo)' },
        { value: 'admin', label: 'Administrador (Instituição)' }
    ];
    if (mockLoggedInUser.role === 'superusuario') {
        roleOptions.push({ value: 'superusuario', label: 'Superusuário (Sistema Inteiro)' });
    }

    if (view === 'list') {
        return (
            <div className="flex flex-col h-full bg-slate-50 relative">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                            <Users size={24} />
                        </div>
                        <div>
                            <h1 className="text-xl md:text-2xl font-bold text-slate-800">Usuários</h1>
                            <p className="hidden md:block text-sm text-slate-500">Controle de acesso e permissões (RBAC)</p>
                        </div>
                    </div>
                    {mockLoggedInUser.role !== 'comum' && (
                        <button
                            onClick={handleCreate}
                            className="flex items-center gap-2 bg-brand-blue hover:bg-brand-blue-light transition-colors text-white px-4 py-2.5 rounded-xl font-semibold shadow-md shadow-brand-blue/20"
                        >
                            <Plus size={18} /> <span className="hidden md:inline">Novo Usuário</span>
                        </button>
                    )}
                </div>

                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col flex-1">
                    <div className="p-4 border-b border-brand-blue/10 bg-brand-blue/5 flex items-center">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                            <input
                                type="text"
                                placeholder="Buscar por Nome, Email ou CPF..."
                                value={searchTerm}
                                onChange={e => setSearchTerm(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                            />
                        </div>
                        <div className="ml-4 flex items-center gap-2 text-sm text-slate-500 hidden sm:flex">
                            <Shield size={16} /> Visão de <strong>{mockLoggedInUser.role.toUpperCase()}</strong>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left">
                            <thead>
                                <tr className="bg-brand-blue/5 text-slate-600 text-sm border-b border-brand-blue/10">
                                    <th className="py-3 px-4 font-semibold uppercase">Nome e Login</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden md:table-cell">Permissão (Perfil)</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden lg:table-cell">Instituição (Órgão)</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden xl:table-cell">Formação Acadêmica</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden xl:table-cell">Matrícula/CF</th>
                                    <th className="py-3 px-4 font-semibold uppercase text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredUsuarios.map(u => (
                                    <tr key={u.id} className="border-b border-brand-blue/5 hover:bg-brand-blue/5 transition-colors">
                                        <td className="py-3 px-4">
                                            <div className="flex items-center gap-3">
                                                <UserCircle className={u.role === 'superusuario' ? 'text-red-500' : u.role === 'admin' ? 'text-brand-blue' : 'text-slate-400'} size={24} />
                                                <div>
                                                    <p className="font-bold text-slate-800">
                                                        {u.nome}
                                                        {!u.ativo && <span className="ml-2 px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-600 font-bold uppercase tracking-wider">Inativo</span>}
                                                    </p>
                                                    <p className="hidden md:block text-sm text-slate-500">{u.email}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="py-3 px-4 hidden md:table-cell">
                                            <span className={`px-2.5 py-1 rounded-md text-xs font-bold uppercase ${u.role === 'superusuario' ? 'bg-red-100 text-red-700' :
                                                u.role === 'admin' ? 'bg-brand-blue/10 text-brand-blue' :
                                                    'bg-slate-100 text-slate-600'
                                                }`}>
                                                {u.role}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-slate-500 hidden lg:table-cell flex flex-col justify-center">
                                            {u.id_orgao ? (
                                                <span className="flex items-center gap-1.5 font-medium"><Building size={14} /> {getOrgaoNome(u.id_orgao)}</span>
                                            ) : (
                                                <span className="text-sm italic">Governança Central</span>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-slate-500 hidden xl:table-cell">
                                            <span className="text-sm font-medium">{getFormacaoNome(u.id_formacao)}</span>
                                        </td>
                                        <td className="py-3 px-4 text-slate-500 hidden xl:table-cell">
                                            <div className="flex flex-col">
                                                <span className="text-xs">Mat: <strong>{u.matricula || '-'}</strong></span>
                                                <span className="text-xs">CF: <strong>{u.carteirafiscal || '-'}</strong></span>
                                            </div>
                                        </td>
                                        <td className="py-3 px-4">
                                            <div className="flex items-center justify-end gap-2">
                                                <button onClick={() => handleEdit(u)} className="p-2 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Edit2 size={18} />
                                                </button>
                                                {mockLoggedInUser.role !== 'comum' && (
                                                    <button onClick={() => handleDelete(u)} className="p-2 text-slate-400 hover:text-red-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                        <Trash2 size={18} />
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {filteredUsuarios.length === 0 && (
                                    <tr>
                                        <td colSpan="4" className="py-8 text-center text-slate-500">Nenhum Usuário encontrado sob suas permissões.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col h-full bg-slate-50">
            <div className="flex items-center gap-4 mb-6">
                <button onClick={() => setView('list')} className="p-2 rounded-xl bg-white text-slate-600 hover:text-brand-blue shadow-sm border border-slate-100 transition-colors">
                    <ArrowLeft size={20} />
                </button>
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                        <Users size={20} />
                    </div>
                    <div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-800">{currentUser.nome ? currentUser.nome : 'Novo Usuário do Sistema'}</h1>
                        <p className="hidden md:block text-sm text-slate-500">Defina os dados, cargos e acessos.</p>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-100 text-slate-800 overflow-hidden flex flex-col flex-1">
                <div className="p-6 flex-1 overflow-y-auto">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 border border-brand-blue/10 bg-brand-blue/5 p-6 rounded-2xl mb-6">
                        <h3 className="col-span-1 md:col-span-2 text-lg font-bold text-brand-blue flex items-center gap-2 mb-2"><UserCircle size={20} /> Dados Pessoais de Acesso</h3>

                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Nome Completo <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                value={currentUser.nome}
                                disabled={mockLoggedInUser.role === 'comum'}
                                onChange={e => setCurrentUser({ ...currentUser, nome: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white disabled:bg-slate-100 disabled:text-slate-500"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">E-mail <span className="text-red-500">*</span></label>
                            <input
                                type="email"
                                value={currentUser.email}
                                disabled={mockLoggedInUser.role === 'comum'}
                                onChange={e => setCurrentUser({ ...currentUser, email: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white disabled:bg-slate-100 disabled:text-slate-500"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">Telefone</label>
                            <input
                                type="text"
                                value={currentUser.telefone || ''}
                                onChange={e => setCurrentUser({ ...currentUser, telefone: e.target.value })}
                                placeholder="(99) 99999-9999"
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">CPF</label>
                            <input
                                type="text"
                                value={currentUser.cpf}
                                disabled={mockLoggedInUser.role === 'comum'}
                                onChange={e => setCurrentUser({ ...currentUser, cpf: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white disabled:bg-slate-100 disabled:text-slate-500"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">Matrícula</label>
                            <input
                                type="text"
                                value={currentUser.matricula || ''}
                                disabled={mockLoggedInUser.role === 'comum'}
                                onChange={e => setCurrentUser({ ...currentUser, matricula: e.target.value })}
                                placeholder="000000"
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white disabled:bg-slate-100 disabled:text-slate-500"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">Carteira Fiscal</label>
                            <input
                                type="text"
                                value={currentUser.carteirafiscal || ''}
                                disabled={mockLoggedInUser.role === 'comum'}
                                onChange={e => setCurrentUser({ ...currentUser, carteirafiscal: e.target.value })}
                                placeholder="CF 0000"
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white disabled:bg-slate-100 disabled:text-slate-500"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">Senha / Credencial</label>
                            <input
                                type="password"
                                value={currentUser.senha || ''}
                                onChange={e => setCurrentUser({ ...currentUser, senha: e.target.value })}
                                placeholder="Deixe em branco para manter a atual"
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5 mt-2">
                            <label className="flex items-center gap-2 cursor-pointer pt-6 group">
                                <input
                                    type="checkbox"
                                    checked={currentUser.ativo}
                                    disabled={mockLoggedInUser.role === 'comum'}
                                    onChange={e => setCurrentUser({ ...currentUser, ativo: e.target.checked })}
                                    className="w-5 h-5 rounded text-brand-blue border-slate-300 focus:ring-brand-blue cursor-pointer disabled:bg-slate-100 disabled:cursor-not-allowed"
                                />
                                <span className={`font-medium transition-colors ${mockLoggedInUser.role === 'comum' ? 'text-slate-400' : 'text-slate-700 group-hover:text-brand-blue'}`}>Permitir Login (Usuário Ativo)</span>
                            </label>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 border border-slate-200 bg-slate-50 p-6 rounded-2xl">
                        <h3 className="col-span-1 md:col-span-2 text-lg font-bold text-slate-700 flex items-center gap-2 mb-2"><Shield size={20} className="text-slate-500" /> Papéis e Associações Institucionais</h3>

                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Permissão de Acesso (Perfil / Role) <span className="text-red-500">*</span></label>
                            <select
                                value={currentUser.role}
                                disabled={mockLoggedInUser.role === 'comum'}
                                onChange={e => setCurrentUser({ ...currentUser, role: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white font-semibold text-slate-700 disabled:bg-slate-100 disabled:text-slate-500"
                            >
                                {roleOptions.map(opt => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Instituição (Órgão Vinculado)</label>
                            <select
                                value={currentUser.id_orgao}
                                disabled={mockLoggedInUser.role === 'admin' || mockLoggedInUser.role === 'comum'} // Admin cannot change institution, Comum cannot either
                                onChange={e => setCurrentUser({ ...currentUser, id_orgao: e.target.value, id_unidade: '', id_cargo: '' })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white disabled:bg-slate-100 disabled:text-slate-500"
                            >
                                <option value="">Sem Órgão / Administração Geral</option>
                                {orgaos.map(o => (
                                    <option key={o.id} value={o.id}>{o.sigla ? `${o.sigla} - ${o.nome}` : o.nome}</option>
                                ))}
                            </select>
                            {mockLoggedInUser.role === 'admin' && <span className="text-xs text-orange-500">Como Administrador, o órgão é restrito ao seu de origem.</span>}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">Unidade Local de Lotação</label>
                            <select
                                value={currentUser.id_unidade}
                                disabled={!currentUser.id_orgao}
                                onChange={e => setCurrentUser({ ...currentUser, id_unidade: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white disabled:bg-slate-100 disabled:text-slate-500"
                            >
                                <option value="">Selecione...</option>
                                {unidades.filter(u => u.id_orgao === currentUser.id_orgao).map(u => (
                                    <option key={u.id} value={u.id}>{u.nome} {u.municipio ? `(${u.municipio})` : ''}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">Cargo do Servidor</label>
                            <select
                                value={currentUser.id_cargo}
                                disabled={!currentUser.id_orgao}
                                onChange={e => setCurrentUser({ ...currentUser, id_cargo: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white disabled:bg-slate-100 disabled:text-slate-500"
                            >
                                <option value="">Selecione...</option>
                                {cargos.filter(c => c.id_orgao === currentUser.id_orgao).map(c => (
                                    <option key={c.id} value={c.id}>{c.nome}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Formação Acadêmica (Graduação)</label>
                            <select
                                value={currentUser.id_formacao || ''}
                                onChange={e => setCurrentUser({ ...currentUser, id_formacao: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            >
                                <option value="">Nenhuma formação selecionada...</option>
                                {formacoes.map(f => (
                                    <option key={f.id} value={f.id}>{f.nome}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                <div className="p-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
                    <button
                        onClick={() => setView('list')}
                        className="px-4 py-2 font-semibold text-slate-600 hover:bg-slate-200 bg-slate-100 rounded-xl transition-colors"
                    ><span className="md:hidden text-lg leading-none">&times;</span> <span className="hidden md:inline">Cancelar</span></button>
                    <button
                        onClick={handleSave}
                        className="flex items-center gap-2 px-6 py-2 bg-brand-blue hover:bg-brand-blue-light text-white font-bold rounded-xl shadow-md shadow-brand-blue/20 transition-colors"
                    >
                        <Save size={18} /> <span className="hidden md:inline">Cadastrar/Salvar</span>
                    </button>
                </div>
            </div>
        </div>
    );
}
