 <style>
     .brand-link .theme-logo {
         float: none;
         max-height: 40px;
         width: auto;
     }

     .brand-link .theme-logo-dark {
         display: none;
     }

     body.dark-mode .brand-link .theme-logo-light {
         display: none;
     }

     body.dark-mode .brand-link .theme-logo-dark {
         display: inline-block;
     }
 </style>

 <aside class="main-sidebar sidebar-light-primary elevation-4">
     <a href="/admin" class="brand-link">
         <img
             src="/adminlte-painel/public/images/logo-dark.png"
             alt="Aurora Tech"
             class="brand-image theme-logo theme-logo-light"
         />
         <img
             src="/adminlte-painel/public/images/logo.png"
             alt="Aurora Tech"
             class="brand-image theme-logo theme-logo-dark"
         />
     </a>

     <div class="sidebar">
         <nav class="mt-2">
             <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
                 <li class="nav-item">
                     <a href="/admin" class="nav-link">
                         <i class="nav-icon fas fa-home"></i>
                         <p>Painel</p>
                     </a>
                 </li>

                 <li class="nav-item">
                     <a href="/usuarios" class="nav-link">
                         <i class="nav-icon fas fa-users"></i>
                         <p>Usuários</p>
                     </a>
                 </li>

                 <li class="nav-item">
                     <a href="/configuracoes" class="nav-link">
                         <i class="nav-icon fas fa-cogs"></i>
                         <p>Configurações</p>
                     </a>
                 </li>
             </ul>
         </nav>
     </div>
 </aside>
