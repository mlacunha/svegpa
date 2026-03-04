<?php 
$bp = $base_path ?? '';
$self = $_SERVER['PHP_SELF'] ?? '';
$cadastroActive = (strpos($self, 'programas') !== false || strpos($self, 'hospedeiros') !== false || strpos($self, 'normas') !== false);
$pragasActive = (strpos($self, 'programas') !== false || strpos($self, 'hospedeiros') !== false || strpos($self, 'normas') !== false);
$levantamentosActive = (strpos($self, 'produtores') !== false || strpos($self, 'propriedades') !== false || strpos($self, 'termo_inspecao') !== false || strpos($self, 'amostragem') !== false);
$auxiliaresActive = (strpos($self, 'orgaos_tipos') !== false || (strpos($self, 'orgaos') !== false && strpos($self, 'orgaos_tipos') === false) || strpos($self, 'unidades') !== false || strpos($self, 'municipios') !== false || strpos($self, 'cargos') !== false || strpos($self, 'usuarios') !== false || strpos($self, 'config_email') !== false);
$relatoriosActive = (strpos($self, 'relatorios') !== false || strpos($self, 'estatisticas') !== false || strpos($self, 'mapas') !== false || strpos($self, 'exportar') !== false);
?>
<nav id="sidebar" class="bg-secondary text-white w-64 flex-shrink-0 transition-transform transform md:translate-x-0 -translate-x-full h-full overflow-y-auto">
    <div class="p-6">
        <div class="flex items-center mb-6">
            <div class="bg-primary p-3 rounded-lg mr-3">
                <i class="fas fa-leaf text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold">SVEG</h2>
                <p class="text-xs text-gray-400">Sistema de Vigilância</p>
            </div>
        </div>
        
        <nav>
            <ul class="space-y-1">
                <li>
                    <a href="<?php echo $bp; ?>dashboard.php" class="flex items-center p-3 rounded-lg hover:bg-primary transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-primary' : ''; ?>">
                        <i class="fas fa-home mr-3"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Bloco Cadastro -->
                <li class="mt-2">
                    <details class="sidebar-group"<?php if ($cadastroActive) echo ' open'; ?>>
                        <summary class="flex items-center justify-between p-3 rounded-lg hover:bg-primary/50 cursor-pointer select-none text-sm font-semibold text-gray-300 uppercase tracking-wider sidebar-summary">
                            <span><i class="fas fa-folder-open mr-2"></i>Cadastro</span>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200 sidebar-chevron"></i>
                        </summary>
                        <ul class="ml-2 mt-1 border-l border-gray-600 pl-2 space-y-0">
                            <!-- Sub-bloco Pragas -->
                            <li>
                                <details class="sidebar-subgroup"<?php if ($pragasActive) echo ' open'; ?>>
                                    <summary class="flex items-center justify-between py-2 px-2 rounded hover:bg-primary/30 cursor-pointer select-none text-gray-300 text-sm sidebar-summary">
                                        <span><i class="fas fa-bug mr-2"></i>Pragas</span>
                                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 sidebar-chevron"></i>
                                    </summary>
                                    <ul class="ml-3 py-1 space-y-0">
                                        <li>
                                            <a href="<?php echo $bp; ?>programas/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'programas') !== false ? 'bg-primary' : ''; ?>">
                                                <i class="fas fa-seedling mr-2 text-sm"></i>
                                                <span>Programas</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo $bp; ?>hospedeiros/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'hospedeiros') !== false ? 'bg-primary' : ''; ?>">
                                                <i class="fas fa-tree mr-2 text-sm"></i>
                                                <span>Hospedeiros</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?php echo $bp; ?>normas/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'normas') !== false ? 'bg-primary' : ''; ?>">
                                                <i class="fas fa-balance-scale mr-2 text-sm"></i>
                                                <span>Normas</span>
                                            </a>
                                        </li>
                                    </ul>
                                </details>
                            </li>
                        </ul>
                    </details>
                </li>

                <!-- Bloco Levantamentos -->
                <li class="mt-2">
                    <details class="sidebar-group"<?php if ($levantamentosActive) echo ' open'; ?>>
                        <summary class="flex items-center justify-between p-3 rounded-lg hover:bg-primary/50 cursor-pointer select-none text-sm font-semibold text-gray-300 uppercase tracking-wider sidebar-summary">
                            <span><i class="fas fa-clipboard-list mr-2"></i>Levantamentos</span>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200 sidebar-chevron"></i>
                        </summary>
                        <ul class="ml-2 mt-1 border-l border-gray-600 pl-2 space-y-0">
                            <li>
                                <a href="<?php echo $bp; ?>produtores/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'produtores') !== false ? 'bg-primary' : ''; ?>">
                                    <i class="fas fa-user-tie mr-2 text-sm"></i>
                                    <span>Produtores</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $bp; ?>propriedades/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'propriedades') !== false ? 'bg-primary' : ''; ?>">
                                    <i class="fas fa-building mr-2 text-sm"></i>
                                    <span>Propriedades</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $bp; ?>termo_inspecao/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'termo_inspecao') !== false ? 'bg-primary' : ''; ?>">
                                    <i class="fas fa-search mr-2 text-sm"></i>
                                    <span>Inspeção</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $bp; ?>amostragem/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'amostragem') !== false ? 'bg-primary' : ''; ?>">
                                    <i class="fas fa-vial mr-2 text-sm"></i>
                                    <span>Amostragem</span>
                                </a>
                            </li>
                        </ul>
                    </details>
                </li>

                <!-- Bloco Auxiliares -->
                <li class="mt-2">
                    <details class="sidebar-group"<?php if ($auxiliaresActive) echo ' open'; ?>>
                        <summary class="flex items-center justify-between p-3 rounded-lg hover:bg-primary/50 cursor-pointer select-none text-sm font-semibold text-gray-300 uppercase tracking-wider sidebar-summary">
                            <span><i class="fas fa-puzzle-piece mr-2"></i>Auxiliares</span>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200 sidebar-chevron"></i>
                        </summary>
                        <ul class="ml-2 mt-1 border-l border-gray-600 pl-2 space-y-0">
                            <li>
                                <a href="<?php echo $bp; ?>orgaos_tipos/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'orgaos_tipos') !== false ? 'bg-primary' : ''; ?>">
                                    <i class="fas fa-tags mr-2 text-sm"></i>
                                    <span>Tipos de Órgãos</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $bp; ?>orgaos/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo (strpos($_SERVER['PHP_SELF'], 'orgaos') !== false && strpos($_SERVER['PHP_SELF'], 'orgaos_tipos') === false) ? 'bg-primary' : ''; ?>">
                                    <i class="fas fa-landmark mr-2 text-sm"></i>
                                    <span>Órgãos</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $bp; ?>unidades/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'unidades') !== false ? 'bg-primary' : ''; ?>">
                                    <i class="fas fa-building mr-2 text-sm"></i>
                                    <span>Unidades</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $bp; ?>municipios/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'municipios') !== false ? 'bg-primary' : ''; ?>">
                                    <i class="fas fa-map-marker-alt mr-2 text-sm"></i>
                                    <span>Municípios</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $bp; ?>cargos/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'cargos') !== false ? 'bg-primary' : ''; ?>">
                                    <i class="fas fa-id-badge mr-2 text-sm"></i>
                                    <span>Cargos</span>
                                </a>
                            </li>
                            <?php if (isAdmin()): ?>
                            <li>
                                <a href="<?php echo $bp; ?>usuarios/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'usuarios') !== false ? 'bg-primary' : ''; ?>">
                                    <i class="fas fa-users mr-2 text-sm"></i>
                                    <span>Usuários</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $bp; ?>config_email/index.php" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'config_email') !== false ? 'bg-primary' : ''; ?>">
                                    <i class="fas fa-envelope mr-2 text-sm"></i>
                                    <span>Config. E-mail</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </details>
                </li>

                <!-- Bloco Relatórios -->
                <li class="mt-2">
                    <details class="sidebar-group"<?php if ($relatoriosActive) echo ' open'; ?>>
                        <summary class="flex items-center justify-between p-3 rounded-lg hover:bg-primary/50 cursor-pointer select-none text-sm font-semibold text-gray-300 uppercase tracking-wider sidebar-summary">
                            <span><i class="fas fa-chart-pie mr-2"></i>Relatórios</span>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200 sidebar-chevron"></i>
                        </summary>
                        <ul class="ml-2 mt-1 border-l border-gray-600 pl-2 space-y-0">
                            <li>
                                <a href="#" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors">
                                    <i class="fas fa-chart-bar mr-2 text-sm"></i>
                                    <span>Estatísticas</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors">
                                    <i class="fas fa-map mr-2 text-sm"></i>
                                    <span>Mapas</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors">
                                    <i class="fas fa-file-alt mr-2 text-sm"></i>
                                    <span>Relatórios</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center py-2 px-2 rounded-lg hover:bg-primary transition-colors">
                                    <i class="fas fa-file-export mr-2 text-sm"></i>
                                    <span>Exportar Dados</span>
                                </a>
                            </li>
                        </ul>
                    </details>
                </li>
                
                <li class="mt-4 pt-2 border-t border-gray-600 space-y-1">
                    <a href="<?php echo $bp; ?>conta.php" class="flex items-center p-3 rounded-lg hover:bg-primary transition-colors">
                        <i class="fas fa-user-cog mr-3"></i>
                        <span>Minha conta</span>
                    </a>
                    <a href="<?php echo $bp; ?>logout.php" class="flex items-center p-3 rounded-lg hover:bg-red-600 transition-colors">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        <span>Sair</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</nav>
