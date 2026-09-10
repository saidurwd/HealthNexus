<div x-data="tenantContext()" class="relative">
    <button @click="open = ! open" class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <span x-text="currentCompanyName || 'Select Company'"></span>
        <div class="ml-1">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </div>
    </button>

    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-50" style="display: none;">
        <div class="py-1">
            <div class="px-4 py-2 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-100">
                Companies
            </div>
            <template x-for="company in companies" :key="company.id">
                <button @click="selectCompany(company)" 
                        :class="currentCompanyId === company.id ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100'"
                        class="block w-full text-left px-4 py-2 text-sm">
                    <span x-text="company.name"></span>
                </button>
            </template>

            <div x-show="currentCompanyId" class="border-t border-gray-100">
                <div class="px-4 py-2 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    Branches
                </div>
                <template x-for="branch in branches" :key="branch.id">
                    <button @click="selectBranch(branch)"
                            :class="currentBranchId === branch.id ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100'"
                            class="block w-full text-left px-4 py-2 text-sm pl-8">
                        <span x-text="branch.name"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function tenantContext() {
    return {
        open: false,
        companies: @json(auth()->user()?->companies ?? []),
        branches: [],
        currentCompanyId: @json(app(\App\Services\TenantContextResolver::class)->getCompanyId()),
        currentBranchId: @json(app(\App\Services\TenantContextResolver::class)->getBranchId()),
        
        get currentCompanyName() {
            const company = this.companies.find(c => c.id === this.currentCompanyId);
            return company ? company.name : null;
        },

        init() {
            if (this.currentCompanyId) {
                this.loadBranches(this.currentCompanyId);
            }
        },

        async loadBranches(companyId) {
            try {
                const response = await fetch(`/api/v1/companies/${companyId}/branches`, {
                    headers: {
                        'Authorization': `Bearer ${document.querySelector('meta[name=\"csrf-token\"]')?.content}`,
                        'Accept': 'application/json',
                    },
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.branches = data.data || [];
                }
            } catch (error) {
                console.error('Failed to load branches:', error);
            }
        },

        async selectCompany(company) {
            this.currentCompanyId = company.id;
            this.currentBranchId = null;
            this.branches = [];
            
            await this.updateContext({ company_id: company.id });
            this.open = false;
        },

        async selectBranch(branch) {
            this.currentBranchId = branch.id;
            
            await this.updateContext({ branch_id: branch.id });
            this.open = false;
        },

        async updateContext(data) {
            try {
                const response = await fetch('/api/v1/tenant/context', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${document.querySelector('meta[name=\"csrf-token\"]')?.content}`,
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify(data),
                });
                
                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Failed to update context:', error);
            }
        },
    }
}
</script>
