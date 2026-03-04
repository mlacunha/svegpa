import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { db } from './db';

export const gerarTermoInspecaoPDF = async (inspecaoId) => {
    try {
        const inspecao = await db.termo_inspecao.get(inspecaoId);
        if (!inspecao) throw new Error("Inspeção não encontrada");

        const propriedade = await db.propriedades.get(inspecao.id_propriedade) || {};

        // No PWA, o produtor geralmente é vinculado com a propriedade. 
        // Verificando as estruturas atuais, id_proprietario pode não estar explicitamente populado em todos os testes locais, 
        // mas tentaremos buscar do banco através da prop "id_proprietario" ou exibiremos o texto preenchido
        const produtor = propriedade.id_proprietario ? await db.produtores.get(propriedade.id_proprietario) : null;

        const fiscal = await db.usuarios.get(inspecao.id_usuario) || {};
        const auxiliar = inspecao.id_auxiliar ? await db.usuarios.get(inspecao.id_auxiliar) : null;

        const orgao = fiscal.id_orgao ? await db.orgaos.get(fiscal.id_orgao) : null;
        const unidade = fiscal.id_unidade ? await db.unidades.get(fiscal.id_unidade) : null;

        const areas = await db.area_inspecionada.where('id_termo_inspecao').equals(inspecaoId).toArray();

        const doc = new jsPDF();

        // Configuração de Fontes e Estilos
        const titleFontSize = 14;
        const textFontSize = 10;

        // ==========================================
        // CABEÇALHO INSTITUCIONAL
        // ==========================================
        doc.setFontSize(titleFontSize);
        doc.setFont("helvetica", "bold");
        const orgaoNome = orgao ? orgao.nome : "GOVERNO DO ESTADO / AGÊNCIA DE DEFESA AGROPECUÁRIA";
        const orgaoSigla = orgao && orgao.sigla ? ` - ${orgao.sigla}` : "";
        doc.text(`${orgaoNome}${orgaoSigla}`.toUpperCase(), 105, 20, { align: 'center' });

        doc.setFontSize(11);
        doc.setFont("helvetica", "normal");

        if (unidade) {
            doc.text(`${unidade.nome.toUpperCase()} - ${(unidade.municipio || '').toUpperCase()}`, 105, 28, { align: 'center' });
        }

        doc.setFont("helvetica", "bold");
        doc.setFontSize(14);
        doc.text("TERMO DE INSPEÇÃO FITOSSANITÁRIA", 105, 45, { align: 'center' });

        doc.setLineWidth(0.5);
        doc.line(14, 48, 196, 48);

        // ==========================================
        // 1. IDENTIFICAÇÃO DO TERMO
        // ==========================================
        doc.setFontSize(11);
        doc.setFont("helvetica", "bold");
        doc.text("1. TERMO DE INSPEÇÃO", 14, 56);

        doc.setFontSize(textFontSize);
        doc.setFont("helvetica", "normal");

        const numTermo = inspecao.termo_inspecao ? String(inspecao.termo_inspecao) : "_________/__________/___________";
        let dateStr = inspecao.data_inspecao ? String(inspecao.data_inspecao) : '';
        if (dateStr && dateStr.includes('-')) {
            const parts = dateStr.split('-');
            if (parts.length === 3) dateStr = `${parts[2]}/${parts[1]}/${parts[0]}`;
        }

        doc.text(`NÚMERO DO TERMO:  ${numTermo}`, 14, 63);
        doc.text(`DATA DA INSPEÇÃO:  ${dateStr || '_________/__________/___________'}`, 130, 63);

        const ufStr = propriedade.UF ? propriedade.UF : 'UF';
        const munStr = propriedade.municipio ? `${propriedade.municipio} - ${ufStr}` : (unidade?.municipio ? `${unidade.municipio}` : '______________________');
        doc.text(`MUNICÍPIO DA INSPEÇÃO:  ${munStr.toUpperCase()}`, 14, 69);

        doc.line(14, 73, 196, 73);

        // ==========================================
        // 2. IDENTIFICAÇÃO DA PROPRIEDADE
        // ==========================================
        doc.setFontSize(11);
        doc.setFont("helvetica", "bold");
        doc.text("2. IDENTIFICAÇÃO DA PROPRIEDADE RURAL", 14, 81);

        doc.setFontSize(textFontSize);
        doc.setFont("helvetica", "normal");

        doc.text(`NOME DA PROPRIEDADE:  ${propriedade.nome || '-'}`, 14, 88);
        doc.text(`N° CADASTRO (CÓDIGO):  ${propriedade.n_cadastro || '-'}`, 130, 88);

        doc.text(`ENDEREÇO / COMUNIDADE:  ${propriedade.endereco || propriedade.bairro || '-'}`, 14, 95);
        doc.text(`MUNICÍPIO:  ${propriedade.municipio || '-'} - ${propriedade.UF || '-'}`, 130, 95);

        const produtorNome = produtor?.nome || '-';
        const produtorDoc = produtor?.cpf_cnpj || propriedade.cpf_cnpj || '-';
        const produtorTel = produtor?.telefone || '-';

        doc.text(`PROPRIETÁRIO / PRODUTOR:  ${produtorNome}`, 14, 102);
        doc.text(`CPF/CNPJ:  ${produtorDoc}`, 130, 102);

        doc.text(`TELEFONE DE CONTATO:  ${produtorTel}`, 14, 109);
        doc.text(`ÁREA TOTAL (HA):  ${String(propriedade.area_total || '-')}`, 130, 109);

        // ==========================================
        // 3. IDENTIFICAÇÃO DA ÁREA INSPECIONADA (TABELA)
        // ==========================================
        const tableData = areas.map(a => [
            `${a.nome_local || '-'}\nÁrea: ${a.area_plantada || '-'} ha`,
            `${a.especie || '-'}\nVar.: ${a.variedade || '-'}`,
            `Mat.: ${a.material_multiplicacao || '-'}\nOrig.: ${a.origem || '-'}`,
            `Idade: ${a.idade_plantio || '-'}\nQtd: ${a.numero_plantas || '-'}`,
            `Lat: ${a.latitude || '-'}\nLon: ${a.longitude || '-'}`,
            `Insp.: ${a.numero_inspecionadas || '-'}\nSusp.: ${a.numero_suspeitas || '-'}`,
            `${a.resultado || '-'}`
        ]);

        autoTable(doc, {
            startY: 115,
            head: [['Identificação / Área Plantada', 'Espécie / Variedade', 'Mat. Mult. / Origem', 'Idade Plantio / Qtd Plantas', 'Coordenadas (GPS)', 'Inspecionadas / Suspeitas', 'Situação']],
            body: tableData,
            theme: 'striped',
            headStyles: { fillColor: [44, 62, 80], fontSize: 8, fontStyle: 'bold', halign: 'center' },
            styles: { fontSize: 7, cellPadding: 2, valign: 'middle' },
            columnStyles: {
                0: { cellWidth: 32 },
                1: { cellWidth: 30 },
                2: { cellWidth: 30 },
                3: { cellWidth: 26 },
                4: { cellWidth: 26 },
                5: { cellWidth: 24 },
                6: { cellWidth: 'auto' }
            },
            margin: { top: 10, left: 10, right: 10 }
        });

        // ==========================================
        // ASSINATURAS PROBATÓRIAS
        // ==========================================
        let finalY = doc.lastAutoTable.finalY + 30;

        // Se a tabela empurrar as assinaturas para o fim da folha, criar nova página
        if (finalY > 230) {
            doc.addPage();
            finalY = 40;
        }

        doc.setLineWidth(0.3);

        // Assinatura 1 (Esquerda) - Fiscal Principal
        doc.line(20, finalY, 90, finalY);
        doc.setFont("helvetica", "bold");
        doc.setFontSize(10);
        doc.text(`TÉCNICO / FISCAL RESPONSÁVEL`, 55, finalY + 5, { align: 'center' });
        doc.setFont("helvetica", "normal");
        doc.setFontSize(9);
        doc.text(`${fiscal.nome || ''}`, 55, finalY + 10, { align: 'center' });
        doc.setFontSize(8);
        const fiscalDocId = (fiscal.carteirafiscal && fiscal.carteirafiscal !== '-') ? fiscal.carteirafiscal : (fiscal.matricula || '-');
        doc.text(`Nº Carteira Fiscal / Matrícula: ${fiscalDocId}`, 55, finalY + 15, { align: 'center' });

        // Assinatura 2 (Direita) - Auxiliar ou Produtor (Se houver)
        if (auxiliar) {
            doc.line(120, finalY, 190, finalY);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.text(`TÉCNICO AUXILIAR`, 155, finalY + 5, { align: 'center' });
            doc.setFont("helvetica", "normal");
            doc.setFontSize(9);
            doc.text(`${auxiliar.nome || ''}`, 155, finalY + 10, { align: 'center' });
            doc.setFontSize(8);
            const auxDocId = (auxiliar.carteirafiscal && auxiliar.carteirafiscal !== '-') ? auxiliar.carteirafiscal : (auxiliar.matricula || '-');
            doc.text(`Nº Carteira Fiscal / Matrícula: ${auxDocId}`, 155, finalY + 15, { align: 'center' });
        } else {
            // Emissão de espaço para o produtor rural assinar
            doc.line(120, finalY, 190, finalY);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.text(`Assinatura do Produtor / Responsável`, 155, finalY + 5, { align: 'center' });
            doc.setFont("helvetica", "normal");
            doc.setFontSize(8);
            doc.text(`${produtorNome}`, 155, finalY + 10, { align: 'center' });
        }

        const termoStr = String(inspecao.termo_inspecao || 'PENDENTE_SYNC').replace(/\//g, '-');
        doc.save(`Termo_Inspecao_${termoStr}.pdf`);
        return true;
    } catch (error) {
        console.error("Erro ao gerar PDF:", error);
        alert("Ocorreu um erro ao gerar o PDF da inspeção.");
        return false;
    }
}

export const gerarTermoColetaPDF = async (inspecaoId) => {
    try {
        const inspecao = await db.termo_inspecao.get(inspecaoId);
        if (!inspecao) throw new Error("Inspeção não encontrada");

        // Puxar apenas as áreas relacionadas que tiverem coleta ativada
        const areas = await db.area_inspecionada.where('id_termo_inspecao').equals(inspecaoId).toArray();
        const areasColeta = areas.filter(a => String(a.coletar_mostra).toLowerCase() === 'true' || a.coletar_mostra === 1 || a.coletar_mostra === true);

        if (areasColeta.length === 0) {
            alert("Não há amostras coletadas nesta inspeção.");
            return false;
        }

        const propriedade = await db.propriedades.get(inspecao.id_propriedade) || {};
        const produtor = propriedade.id_proprietario ? await db.produtores.get(propriedade.id_proprietario) : null;
        const produtorNome = produtor ? produtor.nome : '-';
        const programa = await db.programas.get(inspecao.id_programa) || {};
        const nomeCientifico = programa.nome_cientifico || '-';
        const fiscal = await db.usuarios.get(inspecao.id_usuario) || {};
        const auxiliar = inspecao.id_auxiliar ? await db.usuarios.get(inspecao.id_auxiliar) : null;

        const fiscalFormacao = fiscal.id_formacao ? (await db.formacoes.get(Number(fiscal.id_formacao)) || {}).nome || 'Engenheiro Agrônomo' : 'Engenheiro Agrônomo';
        const fiscalCargo = fiscal.id_cargo ? (await db.cargos.get(fiscal.id_cargo) || {}).nome || 'Auditor Fiscal Federal Agropecuário' : 'Auditor Fiscal Federal Agropecuário';
        const fiscalOrgao = fiscal.id_orgao ? (await db.orgaos.get(fiscal.id_orgao) || {}).nome || 'Ministério da Agricultura e Pecuária MAPA' : 'Ministério da Agricultura e Pecuária MAPA';

        let auxFormacao = 'Engenheiro Agrônomo', auxCargo = 'Auditor Fiscal Federal Agropecuário', auxOrgao = 'Ministério da Agricultura e Pecuária MAPA';
        if (auxiliar) {
            auxFormacao = auxiliar.id_formacao ? (await db.formacoes.get(Number(auxiliar.id_formacao)) || {}).nome || 'Engenheiro Agrônomo' : 'Engenheiro Agrônomo';
            auxCargo = auxiliar.id_cargo ? (await db.cargos.get(auxiliar.id_cargo) || {}).nome || 'Auditor Fiscal Federal Agropecuário' : 'Auditor Fiscal Federal Agropecuário';
            auxOrgao = auxiliar.id_orgao ? (await db.orgaos.get(auxiliar.id_orgao) || {}).nome || 'Ministério da Agricultura e Pecuária MAPA' : 'Ministério da Agricultura e Pecuária MAPA';
        }


        const doc = new jsPDF();

        const textFontSize = 10;

        // ==========================================
        // CABEÇALHO INSTITUCIONAL
        // ==========================================

        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        const orgaoText = "Ministério da Agricultura e Pecuária";
        doc.text(orgaoText.toUpperCase(), 105, 20, { align: 'center' });

        doc.setFontSize(11);
        doc.setFont("helvetica", "normal");
        doc.text("Serviço de Fiscalização, Inspeção e Sanidade Vegetal - SIFISV/PA", 105, 26, { align: 'center' });

        doc.setFontSize(13);
        doc.setFont("helvetica", "bold");
        const numColeta = inspecao.termo_coleta ? String(inspecao.termo_coleta) : "PENDENTE_SYNC";
        doc.text(`TERMO DE COLETA DE AMOSTRAS – TCA ${numColeta}`, 105, 36, { align: 'center' });

        // ==========================================
        // BLOCO DE DADOS BÁSICOS
        // ==========================================
        doc.setFontSize(textFontSize);

        doc.setFont("helvetica", "bold");
        doc.text("Local da Coleta", 14, 48);
        doc.setFont("helvetica", "normal");
        doc.text(`Nome da Propriedade: ${propriedade.nome || '-'}`, 14, 54);
        doc.text(`Endereço da Propriedade: ${propriedade.endereco || propriedade.bairro || '-'}`, 14, 60);

        const ufStr = propriedade.UF ? propriedade.UF : 'PA';
        const munStr = propriedade.municipio ? `${propriedade.municipio} - ${ufStr}` : 'Não Identificado';
        doc.text(`Município: ${munStr.toUpperCase()}`, 14, 66);

        // ==========================================
        // TEXTO DECLARATÓRIO OFICIAL
        // ==========================================
        let dateStr = inspecao.data_inspecao || "_________/__________/___________";
        let dateParts = dateStr.includes('-') ? dateStr.split('-') : null;

        let dia = "___";
        let mesExtenso = "_________";
        let anoDate = "______";

        if (dateParts && dateParts.length === 3) {
            dia = dateParts[2];
            anoDate = dateParts[0];
            const meses = ["janeiro", "fevereiro", "março", "abril", "maio", "junho", "julho", "agosto", "setembro", "outubro", "novembro", "dezembro"];
            mesExtenso = meses[parseInt(dateParts[1], 10) - 1] || "_________";
        }

        const fCart = (fiscal.carteirafiscal && fiscal.carteirafiscal !== '-') ? fiscal.carteirafiscal : (fiscal.matricula || '-');

        let declaracao = "";
        let baseHeader = `No dia ${dia} de ${mesExtenso} do ano de ${anoDate}, no município de ${propriedade.municipio || '_____________'}, estado do Pará,`;

        if (auxiliar) {
            const aCart = (auxiliar.carteirafiscal && auxiliar.carteirafiscal !== '-') ? auxiliar.carteirafiscal : (auxiliar.matricula || '-');
            declaracao = `${baseHeader} ${fiscal.nome || '_____________'}, ${fiscalFormacao}, ${fiscalCargo}, do(a) ${fiscalOrgao}, matrícula/CF ${fCart}, e o(a) ${auxCargo} do(a) ${auxOrgao}, ${auxiliar.nome}, matrícula/CF ${aCart}, realizaram a coleta de amostras de materiais vegetais`;
        } else {
            declaracao = `${baseHeader} eu, ${fiscal.nome || '_____________'}, ${fiscalFormacao}, ${fiscalCargo}, do(a) ${fiscalOrgao}, matrícula/CF ${fCart}, realizei a coleta de amostras de materiais vegetais`;
        }
        declaracao += `, conforme discriminado a seguir:`;

        doc.text(declaracao, 14, 78, { maxWidth: 182, align: "justify" });
        const textHeights = doc.getTextDimensions(declaracao, { maxWidth: 182 }).h;

        // ==========================================
        // TABELA MATERIAL COLETADO
        // ==========================================
        doc.setFont("helvetica", "bold");
        doc.setFontSize(11);
        const yMaterialTitle = 78 + textHeights + 5;
        doc.text("MATERIAL COLETADO", 14, yMaterialTitle);

        let tableData = areasColeta.map(a => {
            let partes = [];
            if (a.raiz) partes.push("Raiz");
            if (a.caule) partes.push("Caule");
            if (a.peciolo) partes.push("Pecíolo");
            if (a.folha) partes.push("Folha");
            if (a.fruto) partes.push("Fruto");
            if (a.flor) partes.push("Flor");
            if (a.semente) partes.push("Semente");

            return [
                a.identificacao_amostra || a.nome_local || '-',
                a.especie || '-',
                partes.join(', ') || '-',
                a.variedade || '-',
                `Lat: ${a.latitude || '-'}\nLon: ${a.longitude || '-'}`,
                nomeCientifico,
                produtorNome
            ];
        });

        if (tableData.length === 0) {
            tableData = [["Nenhum material coletado", "", "", "", "", "", ""]];
        }

        autoTable(doc, {
            startY: yMaterialTitle + 5,
            head: [['Identificação da Amostra', 'Espécie', 'Tipo de Material', 'Variedade', 'Coordenadas Geográficas', 'Análise Solicitada', 'Associado']],
            body: tableData,
            theme: 'striped',
            headStyles: { fillColor: [44, 62, 80], fontSize: 8, fontStyle: 'bold', halign: 'center' },
            styles: { fontSize: 8, cellPadding: 2, valign: 'middle' },
            columnStyles: {
                0: { cellWidth: 25 },
                1: { cellWidth: 20 },
                2: { cellWidth: 25 },
                3: { cellWidth: 20 },
                4: { cellWidth: 32 },
                5: { cellWidth: 30 },
                6: { cellWidth: 'auto' }
            },
            margin: { top: 10, left: 14, right: 14 }
        });

        // ==========================================
        // BLOCO DE ASSINATURAS
        // ==========================================
        let finalY = doc.lastAutoTable.finalY + 30;

        // Se a tabela empurrar as assinaturas para o fim da folha, criar nova página
        if (finalY > 230) {
            doc.addPage();
            finalY = 40;
        }

        doc.setLineWidth(0.3);

        // Assinatura 1 (Esquerda/Centro) - Fiscal Principal
        let assX1 = auxiliar ? 55 : 105;
        let lineX1 = auxiliar ? 20 : 60;
        let lineX2 = auxiliar ? 90 : 150;

        doc.line(lineX1, finalY, lineX2, finalY);
        doc.setFont("helvetica", "bold");
        doc.setFontSize(10);
        let nomeFisCortado = doc.splitTextToSize(fiscal.nome || '', 70);
        doc.text(nomeFisCortado[0], assX1, finalY + 5, { align: 'center' });
        doc.setFont("helvetica", "normal");
        doc.setFontSize(9);
        let formFisCort = doc.splitTextToSize(`${fiscalFormacao}`, 70);
        doc.text(formFisCort[0], assX1, finalY + 10, { align: 'center' });
        let cargoFisCort = doc.splitTextToSize(`${fiscalCargo}`, 70);
        doc.text(cargoFisCort[0], assX1, finalY + 15, { align: 'center' });
        doc.setFontSize(8);
        doc.text(`Matr./CF: ${fCart}`, assX1, finalY + 20, { align: 'center' });

        // Assinatura 2 (Direita) - Auxiliar (Se houver)
        if (auxiliar) {
            const aCart = (auxiliar.carteirafiscal && auxiliar.carteirafiscal !== '-') ? auxiliar.carteirafiscal : (auxiliar.matricula || '-');
            doc.line(120, finalY, 190, finalY);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            let nomeAuxCortado = doc.splitTextToSize(auxiliar.nome || '', 70);
            doc.text(nomeAuxCortado[0], 155, finalY + 5, { align: 'center' });
            doc.setFont("helvetica", "normal");
            doc.setFontSize(9);
            let formAuxCort = doc.splitTextToSize(`${auxFormacao}`, 70);
            doc.text(formAuxCort[0], 155, finalY + 10, { align: 'center' });
            let cargoAuxCort = doc.splitTextToSize(`${auxCargo}`, 70);
            doc.text(cargoAuxCort[0], 155, finalY + 15, { align: 'center' });
            doc.setFontSize(8);
            doc.text(`Matr./CF: ${aCart}`, 155, finalY + 20, { align: 'center' });
        }

        const tcaStr = String(inspecao.termo_coleta || 'PENDENTE_SYNC').replace(/\//g, '-');
        doc.save(`TCA_Termo_Coleta_${tcaStr}.pdf`);
        return true;
    } catch (error) {
        console.error("Erro ao gerar PDF do TCA:", error);
        alert("Ocorreu um erro ao gerar o PDF do TCA.");
        return false;
    }
}
