import Dexie from 'dexie';

export const db = new Dexie('SanvegDB');

// Versionamento do banco de dados local IndexedDB
db.version(1).stores({
    dashboardCache: 'id',
    programas: 'id, nome, codigo',
    propriedades: 'id, nome, municipio',
    produtores: 'id, nome',
    termo_inspecao: 'id, id_propriedade, id_programa, data_inspecao',
    area_inspecionada: 'id, id_termo_inspecao, nome_local',
    syncQueue: '++id, action, route, payload, timestamp'
});

db.version(7).stores({
    dashboardCache: 'id',
    programas: 'id, nome, codigo',
    hospedeiros: 'id, id_programa, nomes_comuns, nome_cientifico',
    normas: 'id, id_programa, nome_norma',
    formacoes: 'id, nome',
    propriedades: 'id, nome, municipio',
    produtores: 'id, nome',
    tipos_orgao: 'id, nome',
    orgaos: 'id, id_tipo_orgao, nome, sigla',
    unidades: 'id, id_orgao, nome, municipio',
    cargos: 'id, id_orgao, nome',
    usuarios: 'id, nome, email, cpf, telefone, matricula, carteirafiscal, role, id_orgao, id_cargo, id_unidade, id_formacao',
    termo_inspecao: 'id, id_propriedade, id_programa, data_inspecao',
    area_inspecionada: 'id, id_termo_inspecao, nome_local',
    syncQueue: '++id, action, route, payload, timestamp'
}).upgrade(async tx => {
    const areas = await tx.area_inspecionada.toArray();
    for (const a of areas) {
        if ((a.id && String(a.id).startsWith('177')) || (a.id_termo_inspecao && String(a.id_termo_inspecao).startsWith('177'))) {
            await tx.area_inspecionada.delete(a.id);
        }
    }

    const termos = await tx.termo_inspecao.toArray();
    for (const t of termos) {
        if (t.id && String(t.id).startsWith('177')) {
            await tx.termo_inspecao.delete(t.id);
        }
    }

    const queues = await tx.syncQueue.toArray();
    for (const q of queues) {
        if (q.payload && q.payload.id && String(q.payload.id).startsWith('177')) {
            await tx.syncQueue.delete(q.id);
        }
    }
});

db.version(6).stores({
    dashboardCache: 'id',
    programas: 'id, nome, codigo',
    hospedeiros: 'id, id_programa, nomes_comuns, nome_cientifico',
    normas: 'id, id_programa, nome_norma',
    formacoes: 'id, nome',
    propriedades: 'id, nome, municipio',
    produtores: 'id, nome',
    tipos_orgao: 'id, nome',
    orgaos: 'id, id_tipo_orgao, nome, sigla',
    unidades: 'id, id_orgao, nome, municipio',
    cargos: 'id, id_orgao, nome',
    usuarios: 'id, nome, email, cpf, telefone, matricula, carteirafiscal, role, id_orgao, id_cargo, id_unidade, id_formacao',
    termo_inspecao: 'id, id_propriedade, id_programa, data_inspecao',
    area_inspecionada: 'id, id_termo_inspecao, nome_local',
    syncQueue: '++id, action, route, payload, timestamp'
}).upgrade(async tx => {
    // Delete all records with Date.now() string IDs that are 13 chars long starting with '177'
    const termos = await tx.termo_inspecao.toArray();
    for (const t of termos) {
        if (t.id && t.id.startsWith('177')) {
            await tx.termo_inspecao.delete(t.id);
        }
    }
    const areas = await tx.area_inspecionada.toArray();
    for (const a of areas) {
        if (a.id && a.id.startsWith('177') || a.id_termo_inspecao && a.id_termo_inspecao.startsWith('177')) {
            await tx.area_inspecionada.delete(a.id);
        }
    }

    // Clear syncQueue just to be sure
    await tx.syncQueue.clear();
});

db.version(5).stores({
    dashboardCache: 'id',
    programas: 'id, nome, codigo',
    hospedeiros: 'id, id_programa, nomes_comuns, nome_cientifico',
    normas: 'id, id_programa, nome_norma',
    formacoes: 'id, nome',
    propriedades: 'id, nome, municipio',
    produtores: 'id, nome',
    tipos_orgao: 'id, nome',
    orgaos: 'id, id_tipo_orgao, nome, sigla',
    unidades: 'id, id_orgao, nome, municipio',
    cargos: 'id, id_orgao, nome',
    usuarios: 'id, nome, email, cpf, telefone, matricula, carteirafiscal, role, id_orgao, id_cargo, id_unidade, id_formacao',
    termo_inspecao: 'id, id_propriedade, id_programa, data_inspecao',
    area_inspecionada: 'id, id_termo_inspecao, nome_local',
    syncQueue: '++id, action, route, payload, timestamp'
});

db.version(4).stores({
    dashboardCache: 'id',
    programas: 'id, nome, codigo',
    hospedeiros: 'id, id_programa, nomes_comuns, nome_cientifico',
    normas: 'id, id_programa, nome_norma',
    propriedades: 'id, nome, municipio',
    produtores: 'id, nome',
    tipos_orgao: 'id, nome',
    orgaos: 'id, id_tipo_orgao, nome, sigla',
    unidades: 'id, id_orgao, nome, municipio',
    cargos: 'id, id_orgao, nome',
    usuarios: 'id, nome, email, cpf, telefone, matricula, carteirafiscal, role, id_orgao, id_cargo, id_unidade',
    termo_inspecao: 'id, id_propriedade, id_programa, data_inspecao',
    area_inspecionada: 'id, id_termo_inspecao, nome_local',
    syncQueue: '++id, action, route, payload, timestamp'
});

db.version(3).stores({
    dashboardCache: 'id',
    programas: 'id, nome, codigo',
    hospedeiros: 'id, id_programa, nomes_comuns, nome_cientifico',
    normas: 'id, id_programa, nome_norma',
    propriedades: 'id, nome, municipio',
    produtores: 'id, nome',
    tipos_orgao: 'id, nome',
    orgaos: 'id, id_tipo_orgao, nome, sigla',
    unidades: 'id, id_orgao, nome, municipio',
    cargos: 'id, id_orgao, nome',
    usuarios: 'id, nome, email, cpf, telefone, role, id_orgao, id_cargo, id_unidade',
    termo_inspecao: 'id, id_propriedade, id_programa, data_inspecao',
    area_inspecionada: 'id, id_termo_inspecao, nome_local',
    syncQueue: '++id, action, route, payload, timestamp'
});

db.version(2).stores({
    dashboardCache: 'id',
    programas: 'id, nome, codigo',
    hospedeiros: 'id, id_programa, nomes_comuns, nome_cientifico',
    normas: 'id, id_programa, nome_norma',
    propriedades: 'id, nome, municipio',
    produtores: 'id, nome',
    tipos_orgao: 'id, nome',
    orgaos: 'id, id_tipo_orgao, nome, sigla',
    unidades: 'id, id_orgao, nome, municipio',
    cargos: 'id, id_orgao, nome',
    usuarios: 'id, nome, email, cpf, telefone, role, id_orgao, id_cargo, id_unidade',
    termo_inspecao: 'id, id_propriedade, id_programa, data_inspecao',
    area_inspecionada: 'id, id_termo_inspecao, nome_local',
    syncQueue: '++id, action, route, payload, timestamp'
});

export async function offlineSave(tableName, data) {
    await db[tableName].put(data);
    await db.syncQueue.add({
        action: 'put',
        route: tableName,
        payload: data,
        timestamp: Date.now()
    });
}

export async function offlineDelete(tableName, id) {
    await db[tableName].delete(id);
    await db.syncQueue.add({
        action: 'delete',
        route: tableName,
        payload: { id },
        timestamp: Date.now()
    });
}
