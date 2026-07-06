<!-- ========== App Menu Start ========== -->
<div class="main-nav">
   <div class="logo-box">
         <a href="{{ route('dashboard') }}" class="logo-dark">
            <img src="{{ asset('backend/assets/fav-icon.png') }}" class="logo-sm" alt="logo sm">
            <img src="{{ asset('backend/assets/logo.png') }}" class="logo-lg img-fluid" alt="logo dark">
         </a>

         <a href="{{ route('dashboard') }}" class="logo-light text-center">
            <img src="{{ asset('backend/assets/fav-icon.png') }}" class="logo-sm" alt="logo sm">
            <img src="{{ asset('backend/assets/logo.png') }}" class="logo-lg img-fluid" alt="logo light">
         </a>
   </div>
   <button type="button" class="button-sm-hover text-info" aria-label="Show Full Sidebar">
      <iconify-icon icon="solar:double-alt-arrow-right-bold-duotone" class="button-sm-hover-icon"></iconify-icon>
   </button>
   <div class="scrollbar" data-simplebar>
      <ul class="navbar-nav" id="navbar-nav">
         <!-- Dashboard -->
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
               <span class="nav-icon">
                  <iconify-icon icon="solar:widget-5-bold-duotone"></iconify-icon>
               </span>
               <span class="nav-text">Dashboard</span>
            </a>
         </li>
         <!-- Manage Products -->
         @php
         $currentRoute = Route::currentRouteName();
         $productRoutes = [
            'label*',
            'category*',
            'product.*',
            'attributes*',
            'manage-storage*',
            'inventory.*'
         ];
         $isProductActive = request()->routeIs($productRoutes);
         @endphp
         <li class="nav-item">
            <a class="nav-link menu-arrow {{ $isProductActive ? 'active' : 'collapsed' }}"
               href="#sidebarProducts_2"
               data-bs-toggle="collapse"
               role="button"
               aria-expanded="{{ $isProductActive ? 'true' : 'false' }}"
               aria-controls="sidebarProducts_2">

               <span class="nav-icon">
                     <iconify-icon icon="solar:t-shirt-bold-duotone"></iconify-icon>
               </span>

               <span class="nav-text">Manage Products</span>
            </a>

            <div class="collapse {{ $isProductActive ? 'show' : '' }}" id="sidebarProducts_2">
               <ul class="nav sub-navbar-nav">

                     <li class="sub-nav-item">
                        <a class="sub-nav-link {{ request()->routeIs('label*') ? 'active' : '' }}"
                           href="{{ route('label') }}">Label</a>
                     </li>
                     <li class="sub-nav-item">
                        <a class="sub-nav-link {{ request()->routeIs('tags*') ? 'active' : '' }}"
                           href="{{ route('tags.index') }}">Tags</a>
                     </li>

                     <li class="sub-nav-item">
                        <a class="sub-nav-link {{ request()->routeIs('category*') ? 'active' : '' }}"
                           href="{{ route('category') }}">Main Category</a>
                     </li>

                     <li class="sub-nav-item">
                        <a class="sub-nav-link {{ request()->routeIs('product.*') ? 'active' : '' }}"
                           href="{{ route('product.index') }}">Product</a>
                     </li>

                     <li class="sub-nav-item">
                        <a class="sub-nav-link {{ request()->routeIs('attributes*') ? 'active' : '' }}"
                           href="{{ route('attributes') }}">Attributes</a>
                     </li>

                     <li class="sub-nav-item">
                        <a class="sub-nav-link {{ request()->routeIs('manage-storage*') ? 'active' : '' }}"
                           href="{{ route('manage-storage') }}">Storage</a>
                     </li>

                     <li class="sub-nav-item">
                        <a class="sub-nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}"
                           href="{{ route('inventory.index') }}">Inventory</a>
                     </li>

               </ul>
            </div>
         </li>
         
         @php
         $customerRoutes = ['manage-customer.index', 'manage-customer.create', 'manage-customer.edit', 'manage-customer.show'];
         $isCustomerActive = in_array($currentRoute, $customerRoutes);
         @endphp
         <li class="nav-item">
            <a class="nav-link menu-arrow {{ $isCustomerActive ? '' : 'collapsed' }} {{ $isCustomerActive ? 'active' : '' }}"
               href="#sidebar_customer"
               data-bs-toggle="collapse"
               role="button"
               aria-expanded="{{ $isCustomerActive ? 'true' : 'false' }}"
               aria-controls="sidebar_customer">
               <span class="nav-icon">
                  <iconify-icon icon="solar:users-group-two-rounded-bold-duotone"></iconify-icon>
               </span>
               <span class="nav-text">Manage Customer</span>
            </a>
            <div class="collapse {{ $isCustomerActive ? 'show' : '' }}" id="sidebar_customer">
               <ul class="nav sub-navbar-nav">
                  <li class="sub-nav-item">
                     <a class="sub-nav-link {{ request()->routeIs('manage-customer.*') ? 'active' : '' }}" href="{{ route('manage-customer.index') }}">Customer</a>
                  </li>
               </ul>
            </div>
         </li>

         @php
         $currentRoute = Route::currentRouteName();

         $orderRoutes = [
            'order-list*',
            'order-details*',
            'edit-order*',
            'update-order-status*',
            'download-invoice*'
         ];

         $isOrderActive = request()->routeIs($orderRoutes);
         @endphp

         <li class="nav-item">
            <a class="nav-link menu-arrow {{ $isOrderActive ? 'active' : 'collapsed' }}"
               href="#sidebarOrders"
               data-bs-toggle="collapse"
               role="button"
               aria-expanded="{{ $isOrderActive ? 'true' : 'false' }}"
               aria-controls="sidebarOrders">

               <span class="nav-icon">
                     <iconify-icon icon="solar:cart-large-bold-duotone"></iconify-icon>
               </span>

               <span class="nav-text">Manage Orders</span>
            </a>

            <div class="collapse {{ $isOrderActive ? 'show' : '' }}" id="sidebarOrders">
               <ul class="nav sub-navbar-nav">

                     <li class="sub-nav-item">
                        <a class="sub-nav-link {{ request()->routeIs('order-list*') ? 'active' : '' }}"
                           href="{{ route('order-list') }}">
                           Order List
                        </a>
                     </li>

               </ul>
            </div>
         </li>
         <!-- Manage Other Section -->
         @php
         $otherRoutes = [
         'manage-banner.index', 'manage-banner.create', 'manage-banner.edit',
         'manage-client.index', 'manage-client.create', 'manage-client.edit',
         'manage-testimonials.index', 'manage-testimonials.create', 'manage-testimonials.edit',
         'manage-blog.index', 'manage-blog.create', 'manage-blog.edit',
         'manage-faq.index', 'manage-faq.create', 'manage-faq.edit',
         'primary-category.index', 'primary-category.create', 'primary-category.edit',
         'manage-industry.index', 'manage-industry.create', 'manage-industry.edit',
         'industry-category.index'
         ];
         $isOtherActive = in_array($currentRoute, $otherRoutes);
         @endphp
         <li class="nav-item">
            <a class="nav-link menu-arrow {{ $isOtherActive ? '' : 'collapsed' }} {{ $isOtherActive ? 'active' : '' }}"
               href="#sidebar_banner"
               data-bs-toggle="collapse"
               role="button"
               aria-expanded="{{ $isOtherActive ? 'true' : 'false' }}"
               aria-controls="sidebar_banner">
               <span class="nav-icon">
                  <iconify-icon icon="solar:checklist-bold-duotone"></iconify-icon>
               </span>
               <span class="nav-text">Manage Other Section</span>
            </a>
            <div class="collapse {{ $isOtherActive ? 'show' : '' }}" id="sidebar_banner">
               <ul class="nav sub-navbar-nav">
                  <li class="sub-nav-item">
                     <a class="sub-nav-link {{ request()->routeIs('industry-category.*') ? 'active' : '' }}" href="{{ route('industry-category.index') }}">
                        Industry Category
                     </a>
                     <a class="sub-nav-link {{ request()->routeIs('manage-industry.*') ? 'active' : '' }}" href="{{ route('manage-industry.index') }}">
                        Manage Industry
                     </a>
                  </li>
                  <li class="sub-nav-item">
                     <a class="sub-nav-link {{ request()->routeIs('primary-category.*') ? 'active' : '' }}" href="{{ route('primary-category.index') }}">Primary Category</a>
                  </li>
                  <li class="sub-nav-item">
                     <a class="sub-nav-link {{ request()->routeIs('manage-banner.*') ? 'active' : '' }}" href="{{ route('manage-banner.index') }}">Banner</a>
                  </li>
                  <li class="sub-nav-item">
                     <a class="sub-nav-link {{ request()->routeIs('manage-client.*') ? 'active' : '' }}" href="{{ route('manage-client.index') }}">Our Clients</a>
                  </li>
                  <li class="sub-nav-item">
                     <a class="sub-nav-link {{ request()->routeIs('manage-testimonials.*') ? 'active' : '' }}" href="{{ route('manage-testimonials.index') }}">Testimonials</a>
                  </li>
                  <li class="sub-nav-item">
                     <a class="sub-nav-link {{ request()->routeIs('manage-blog.*') ? 'active' : '' }}" href="{{ route('manage-blog.index') }}">Blog</a>
                  </li>
                  <li class="sub-nav-item">
                     <a class="sub-nav-link {{ request()->routeIs('manage-faq.*') ? 'active' : '' }}" href="{{ route('manage-faq.index') }}">FAQ</a>
                  </li>
               </ul>
            </div>
         </li>
      </ul>
   </div>
</div>
<!-- ========== App Menu End ========== -->