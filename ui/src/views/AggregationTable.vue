<template>
    <DebugDisplay :debug="serverdebug"></DebugDisplay>

    <div class="bg-brand-light-purple/10 border rounded-md mt-2 border-gray-300 shadow-sm">

        <div class="p-2">
            <div class="flex gap-2 justify-start mb-4">
                <LevelOneSelect  @levelchange="levelOneChange"></LevelOneSelect>
                <GroupSelect v-if="level1category" @groupselected="groupselected"></GroupSelect>
            </div>

            <!-- Buttons line -->
            <AggregationButtons
                v-if="level1category && aggregationsupported"
                :categoryid="categoryid"
                :gradeitemid="gradeitemid"
                :groupid="groupid"
                :toplevel="toplevel"
                :atype="atype"
                :allowconversion="allowconversion"
                :allowrelease="allowrelease"
                :released="released"
                :staffuserid="staffuserid"
                :caneditgrades="caneditgrades"
                @refreshtable="table_update"
                ></AggregationButtons>
        </div>

        <AlertsBlock :errors="errors" />
    </div>

    <!-- Aggregation is not possible -->
     <UAlert v-if="!aggregationsupported" variant="error" class="my-5">{{  mstrings.aggregationnotsupported }}</UAlert>

    <div v-if="level1category && aggregationsupported" class="mt-2">

        <!-- Breadcrumb trail -->
        <div v-if="breadcrumb.length > 1" class="my-3 overflow-visible">
            <div class="text-sm p-2 rounded-lg border border-university-blue/40 bg-university-blue/10 text-university-blue shadow-sm">
                <ul class="flex flex-wrap items-center gap-2">
                    
                    <li class="flex items-center text-university-blue/70">
                        <FolderOpen :size="18" />
                    </li>

                    <li 
                        v-for="(item, index) in breadcrumb" 
                        :key="item.id"
                        class="flex items-center gap-2"
                    >
                        <span v-if="index > 0" class="text-university-blue/40 select-none">/</span>

                        <UTooltip :text="item.fullname">
                            <a
                                href="#"
                                @click.prevent="expand_clicked(item.id)"
                                class="hover:underline transition-colors block py-0.5"
                                :class="{ 
                                    'font-bold text-university-blue': index === breadcrumb.length - 1,
                                    'text-university-blue/80 hover:text-university-blue': index !== breadcrumb.length - 1
                                }"
                            >
                                {{ item.shortname }}
                            </a>
                        </UTooltip>

                    </li>
                </ul>
            </div>
        </div>

        <!-- Please wait spinner -->
        <PleaseWait v-if="loading"></PleaseWait>

        <EasyDataTable
            v-if="!loading"
            alternating
            buttons-pagination
            :current-page="currentpage"
            sort-by="displayname"
            sort-type="asc"
            table-class-name="uofg-table"
            header-text-direction="center"
            :body-item-class-name="table_item_class"
            :header-item-class-name="header_item_class"
            :items="users"
            :headers="headers"
            :filter-options="table_filter"
            :rows-items="[25,50,100,250]"
            @update-page-items="pagination_change"
        >

            <!-- additional information in header cells -->
            <template #header="header">
                <div v-if="header.value == 'back'">
                    <UButton @click="expand_clicked(backid)">
                        <ArrowBigLeft :size="18" /> Back
                    </UButton>
                </div>
                <div v-else class="aggregation-header flex gap-x-2">
                    <div>
                        <UTooltip :text="header.fullname">
                            <div>
                                <!-- column title -->
                                <InfoButton v-if="header.gradeitemid" :itemid="header.gradeitemid" :text="header.text" size="lg" color="text-warning"></InfoButton>
                                <span v-else>{{ header.text }}</span>
                            </div>
                            <div v-if="!header.infocol && showweights">{{ header.weight }}%</div>
                            <div v-if="header.gradetype">{{ header.gradetype }} <span v-if="!header.isscale">({{ header.grademax }})</span></div>
                            <div v-if="header.resititem" class="badge badge-success">{{ mstrings.reassessment}}</div>
                        </UTooltip>
                        <div class="py-1" v-if="header.strategy">
                            <i>{{ header.strategy }}</i>
                        </div>
                        <div v-if="header.atype">
                            ({{ formattedatype }})
                        </div>
                    </div>
                </div>
                <div v-if="header.categoryid">
                    <UButton class="ml-2" @click="expand_clicked(header.categoryid)" aria-label="Drill down into grade category.">
                        <ArrowBigRight :size="18" :stroke-width="1" />
                    </UButton>
                </div>
            </template>

            <!-- all items (yes this is complicated) -->
            <!-- point is to iterate over field names to maniuplate data in individual field items -->
            <template v-for="header in headers" v-slot:[header.slot]="item">

                <div class="inline-flex items-center gap-1">

                    <!-- strikethrough if data is dropped -->
                    <!-- bold if admin -->
                    <!-- there HAS to be an easier way -->
                    <span :class="itemclasses(item[header.value])">
                        <s v-if="item[header.value].dropped">
                            <b v-if="item[header.value].isadmin">{{ item[header.value].data }}</b>
                            <GradeColor v-else :grade="item[header.value].data"></GradeColor>
                            <!-- <span v-else :class="gradecolorclass(item[header.value].data)">{{ item[header.value].data }}</span> -->
                        </s>
                        <span v-else>
                            <b v-if="item[header.value].isadmin">{{ item[header.value].data }}</b>
                            <GradeColor v-else :grade="item[header.value].data"></GradeColor>
                            <!-- <span v-else :class="gradecolorclass(item[header.value].data)">{{ item[header.value].data }}</span> -->
                        </span>
                    </span>

                    <!-- add/override grade -->
                    <OverrideGrade
                        v-if="item[header.value].available"
                        :itemid = "header.gradeitemid"
                        :categoryid = "header.categoryid"
                        :selectedcategoryid = "level1category"
                        :userid = "item.id"
                        :gradehidden = "item[header.value].hidden"
                        :overridden = "item[header.value].overridden"
                        :itemname = "header.fullname"
                        :name = "item.displayname"
                        :showweights = "header.showweights"
                        :released = "header.released"
                        :caneditgrades = "caneditgrades"
                        @gradeadded = "grade_changed(item.id)"
                    ></OverrideGrade>
                </div>
            </template>

            <!-- User picture column -->
            <template #item-slotuserpicture="item">
                <a :href="item.profileurl" class="avatar">
                    <img :src="item.pictureurl" :alt="item.displayname" class="rounded-full" width="35" height="35"/>
                </a>
            </template>

            <!-- Resit required -->
            <template #item-resitrequired="item">
                <a v-if="caneditgrades" class="cursor-pointer" @click.prevent="resit_clicked(item.id, !item.resitrequired)">
                    <span v-if="item.resitrequired" class="badge badge-success">{{ mstrings.yes }}</span>
                    <span v-else class="badge badge-secondary badge-soft">{{ mstrings.no }}</span>
                </a>
                <span v-if="!caneditgrades">
                    <span v-if="item.resitrequired" class="badge badge-success">{{ mstrings.yes }}</span>
                    <span v-else class="badge badge-secondary badge-soft">{{ mstrings.no }}</span>
                </span>
            </template>

            <!-- Completion -->
            <template #item-completed="item">
                {{ item.completed }}%
            </template>

            <!-- Releasegrade -->
            <template #item-releasegrade="item">
                <div v-if="!toplevel">
                    <GradeColor :grade="item.releasegrade"></GradeColor>
                    <!--<span :class="gradecolorclass(item.releasegrade)">{{ item.releasegrade }}</span> -->
                    <span v-if="item.mismatch">
                        <br />
                        <span class="badge badge-error mt-1">MISMATCH</span>
                    </span>
                </div>
            </template>

            <!-- Total -->
            <template #item-total="item">
                <div class="inline-flex items-center gap-1">
                    <div>
                        <span v-if="item.error">{{ item.error }}</span>
                        <GradeColor v-else :grade="item.displaygrade"></GradeColor>
                        <!-- <span :class="itemclasses(item).concat(gradecolorclass(item.displaygrade))" v-else>{{ item.displaygrade }}</span> -->
                        <span v-if="item.alteredweight">
                            <br />
                            <span class="badge badge-warning mt-1">ALTERED</span>
                         </span>
                    </div>
                    <div>
                        <!-- add/override for total grade -->
                        <OverrideGrade
                            :toplevel="toplevel"
                            :itemid = "gradeitemid"
                            :selectedcategoryid = "level1category"
                            :categoryid = "categoryid"
                            :userid = "item.id"
                            :gradehidden = "false"
                            :overridden = "item.overridden"
                            :itemname = "item.itemname"
                            :name = "item.displayname"
                            :showweights = "showweights"
                            :released = "false"
                            :caneditgrades = "caneditgrades"
                            @gradeadded = "grade_changed(item.id)"
                        ></OverrideGrade>
                    </div>
                </div>
            </template>

        </EasyDataTable>
    </div>
</template>

<script setup lang="ts">
    import {ref, computed, onMounted, nextTick } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import LevelOneSelect from '@/components/Common/LevelOneSelect.vue';
    import GroupSelect from '@/components/Common/GroupSelect.vue';
    import InfoButton from '@/components/Common/InfoButton.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import AggregationButtons from '@/components/Aggregation/AggregationButtons.vue';
    import OverrideGrade from '@/components/Aggregation/OverrideGrade.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { ArrowBigRight, ArrowBigLeft, FolderOpen } from '@lucide/vue';
    import type { IBreadcrumb, IColumn, IUser, IUserField, IWarning, IError } from '@/js/Interfaces';
    import type { Header, Item } from "vue3-easy-data-table";
    import UAlert from '@/components/Common/UAlert.vue';
    import UTooltip from '@/components/Common/UTooltip.vue';
    import AlertsBlock from '@/components/Common/AlertsBlock.vue';
    import GradeColor from '@/components/Common/GradeColor.vue';
    import { useFilter } from '@/stores/filter';
    import UButton from '@/components/Common/UButton.vue';

    interface IAggregationHeader {
        infocol?: boolean;
        text: string;
        value: string;
        sortType?: string;
        sortable?: boolean;
        excludeempty?: boolean;
        strategy?: string;
        categoryid?: number;
        fullname?: string;
        gradeitemid?: number;
        grademax?: number;
        gradetype?: string;
        isscale?: boolean;
        released?: boolean;
        resititem?: boolean;
        showweights?: boolean;
        slot?: string;
        weight?: number;
        atype?: string;
    }

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const level1category = ref(0);
    const loading = ref(true);
    const currentpage = ref(1);
    const rowsperpage = ref(25);
    const datatablekey = ref(1);
    const aggregationsupported = ref(true);
    const categoryid = ref(0);
    const gradeitemid = ref(0);
    const groupid = ref(0);
    const items = ref([]);
    const users = ref< IUser[] >([]);
    const columns = ref< IColumn[] >([]);
    const categories = ref([]);
    const breadcrumb = ref< IBreadcrumb[] >([]);
    const backid = ref(0);
    const toplevel = ref(false);
    const completed = ref(0);
    const atype = ref('');
    const formattedatype = ref('');
    const warnings = ref< IWarning[] >([]);
    const strategy = ref('');
    const debug = ref([]);
    const conversion = ref('');
    const allowconversion = ref(false);
    const serverdebug = ref({});
    const allowrelease = ref(false);
    const released = ref(false);
    const showweights = ref(false);
    const excludeempty = ref(false);
    const staffuserid = ref(0);
    const caneditgrades = ref(false);
    const completionused = ref(false);
    const filterstore = useFilter();
    const issetupcomplete = ref(false);
    const { firstname, lastname } = storeToRefs( filterstore );

    /**
     * onMounted, get write grades capability
     */
    onMounted(() => {

        moodleFetch(
            'local_gugrades_has_capability',
            {
                capability: 'local/gugrades:editgrades'
            }
        )
        .then((result: any) => {
            caneditgrades.value = result.hascapability;
        })
        .catch((error) => {
            console.error(error);
            debug.value = error;
        });
    });

    /**
     * Table name filter
     */
    const table_filter = computed(() => {
        const options = [];

        if (firstname.value != 'all') {
            options.push({
                field: 'firstinitial',
                comparison: '=',
                criteria: firstname.value,
            });
        }

        if (lastname.value != 'all') {
            options.push({
                field: 'lastinitial',
                comparison: '=',
                criteria: lastname.value,
            });
        }

        return options.length ? options : null;
    });

    /**
     * Page changed by pagination
     */
    function page_changed(newpage: number) {
        currentpage.value = newpage;
        datatablekey.value++;
    }

    /**
     * Number of pages changed
     */
    function pagination_change(rows: any) {
        rowsperpage.value = rows.length;
        currentpage.value = 1;
        datatablekey.value++;
    }

    /**
     * Work out border classes for item
     */
    function itemclasses(item: IUserField) {
        if (item.overridden) {
            return ['border', 'border-danger', 'rounded', 'p-1']
        }
        if (item.hidden) {
            return ['border', 'border-warning', 'rounded', 'p-1']
        }
        return [];
    }

    /**
     * Capture change to top level category dropdown
     * @param {*} level
     */
    function levelOneChange(level: number) {

        level1category.value = level;
        categoryid.value = level1category.value;
        if (categoryid.value) {

            // Don't fire update during initial setup
            table_update();
        }
    }

    /**
     * Capture change to group
     */
     function groupselected(gid: number) {
        const newgid = Number(gid);
        if (newgid === groupid.value) return;
        groupid.value = newgid;

        // Don't fire during initial setup.
        table_update();
    }

    /**
     * Get class name for table items
     */
     function table_item_class(column: string) {

        // Hide name initial columns
        if ((column == 'firstinitial') || (column == 'lastinitial')) {
            return 'hidden';
        }
        if (column != 'displayname') {
            return '!text-center';
        }
    }

    /**
     * Get class name for header items
     */
     function header_item_class(header: IAggregationHeader) {
        if ((header.value == 'firstinitial') || (header.value == 'lastinitial')) {
            return 'hidden';
        }
    }

    /**
     * Resit required 'pill' clicked
     */
    function resit_clicked(userid: number, required: boolean) {

        moodleFetch(
            'local_gugrades_resit_required',
            {
                userid: userid,
                required: required,
            }
        )
        .then(() => {
            user_update(userid);

        })
        .catch((error) => {
            window.console.error(error);
            serverdebug.value = error;
        });
    }

    /**
     * Grade has been modified for user
     */
    function grade_changed(userid: number) {
        user_update(userid);
    }

    /**
     * Add columns to user array
     */
    function process_users(users: IUser[]) {
        users.forEach(user => {
            user.fields.forEach(field => {
                user[field.fieldname] = {
                    userid: user.id,
                    data: field.display,
                    dropped: field.dropped,
                    isadmin: field.isadmin,
                    hidden: field.hidden,
                    overridden: field.overridden,
                    available: field.available,
                };
            })
        });

        return users;
    }

    /**
     * Process columns for single user
     */
    function process_user(user: IUser) {
        user.fields.forEach(field => {
                user[field.fieldname] = {
                    userid: user.id,
                    data: field.display,
                    dropped: field.dropped,
                    isadmin: field.isadmin,
                    hidden: field.hidden,
                    overridden: field.overridden,
                    available: field.available,
                };
        });

        return user;
    }

    /**
     * Show the correct string for the aggregation type (atype)
     */
    function get_formattedatype() {
        if (atype.value == 'A') {
            return 'GGS1';
        } else if (atype.value == 'B') {
            return 'GGS2';
        } else if (atype.value == 'P') {
            return mstringstore.getMstring('points') + ' 100';
        } else if (atype.value == 'C') {
            return mstringstore.getMstring('converted');
        } else if (atype.value == 'E') {
            return 'Error';
        } else {
            return '[[' + atype.value + ']]';
        }
    };

    /**
     * mstring helper
     */
    const ms = (key: string): string => mstringstore.getMstring(key) ?? '';

    /**
     * Create list of headers for EasyDataTable
     * (infocol = true, means that the column has no grade data)
     */
    const headers = computed<IAggregationHeader[]>(() => {
        let heads: IAggregationHeader[] = [];

        // User identification.
        heads.push({text: 'firstinitial', value: 'firstinitial'});
        heads.push({text: 'lastinitial', value: 'firstinitial'});
        heads.push({text: ms('userpicture'), value: "slotuserpicture", infocol: true});
        heads.push({text: mstrings.value['note'] ?? '', value: "slotnote", infocol: true});
        heads.push({text: ms('firstnamelastname'), value: "displayname", sortable: true, infocol: true});
        heads.push({text: ms('idnumber'), value: "idnumber", sortable: true, infocol: true});

        if (!toplevel.value) {
            heads.push({text: '??', value: "back"});
        }

        columns.value.forEach(column => {
            heads.push({
                gradeitemid: column.gradeitemid,
                text: column.shortname ?? '',   // <-- also guard this if it can be undefined
                value: column.fieldname,
                slot: 'item-' + column.fieldname,
                weight: column.weight,
                fullname: column.fullname,
                categoryid: column.categoryid,
                gradetype: column.gradetype,
                grademax: column.grademax,
                isscale: column.isscale,
                strategy: column.strategy,
                showweights: column.showweights,
                released: column.released,
                resititem: column.isresitgradeitem,
            });
        });

        if (toplevel.value) {
            heads.push({text: ms('resitrequired'), value: "resitrequired", infocol: true});

            if (completionused.value) {
                heads.push({text: ms('completed'), value: "completed", infocol: true});
            }

            heads.push({
                text: ms('coursetotal'),
                value: "total",
                infocol: true,
                strategy: strategy.value,
                excludeempty: excludeempty.value,
            });
        } else {
            let headerstrategy = strategy.value;
            if (conversion.value) {
                headerstrategy = headerstrategy + ' by ' + conversion.value;
            }

            heads.push({
                text: ms('subcattotal'),
                atype: atype.value,
                grademax: 100,
                value: "total",
                infocol: true,
                strategy: headerstrategy,
            });
        }

        if (released.value && !toplevel.value) {
            heads.push({text: ms('released'), value: 'releasegrade', infocol: true});
        }

        return heads;
    });

    /**
     * Update single user (when something changes)
     */
    function user_update(userid: number) {

        moodleFetch(
            'local_gugrades_get_aggregation_user',
            {
                gradecategoryid: categoryid.value,
                userid: userid,
            }
        )
        .then((result: any) => {
            const found = users.value.findIndex((user) => {
                return user.id == userid;
            });
            if (found > -1) {
                users.value[found] = process_user(result);
            }
        })
        .catch((error) => {
            window.console.error(error);
            serverdebug.value = error;
        });
    }

    /**
     * convert warnings to errors
     */
    const errors = computed< IError[] >(() => {
        return warnings.value.map(warning => {
            return {
                warning: warning.message,
                help: '',
                level: 'warning'
            }
        });
    });

    /**
     * Update table (when something changes)
     */
    function table_update() {

        // This is to prevent other things firing update during page load.
        //nextTick();
        issetupcomplete.value = true;

        // If we happen to end up here with no categoryid then just bail out.
        if (!Number.isInteger(categoryid.value)) {
            return;
        }

        loading.value = true;

        moodleFetch(
            'local_gugrades_get_aggregation_page',
            {
                gradecategoryid: categoryid.value,
                firstname: '',
                lastname: '',
                groupid: groupid.value,
                aggregate: false,
            }
        )
        .then((result: any) => {
            console.log(result);
            aggregationsupported.value = result.aggregationsupported;
            users.value = result.users;
            warnings.value = result.warnings;
            columns.value = result.columns;
            breadcrumb.value = result.breadcrumb;
            toplevel.value = result.toplevel;
            atype.value = result.atype;
            gradeitemid.value = result.gradeitemid;
            strategy.value = result.strategy;
            debug.value = result.debug;
            conversion.value = result.conversion;
            allowconversion.value = result.allowconversion;
            allowrelease.value = result.allowrelease;
            released.value = result.released;
            showweights.value = result.showweights;
            excludeempty.value = result.excludeempty;
            staffuserid.value = result.staffuserid;
            completionused.value = result.completionused;

            if (aggregationsupported.value) {

                // Get id of one back from breadcrumb
                if (breadcrumb.value.length >= 2) {
                    const crumb = breadcrumb.value.slice(-2);
                    if (crumb.length > 0) {
                        backid.value = crumb[0]!.id;
                    }
                }

                users.value = process_users(users.value);
                formattedatype.value = get_formattedatype();
            }

            loading.value = false;
        })
        .catch((error) => {
            window.console.error(error);
            serverdebug.value = error;
        });
    }

    /**
     * Expand button was clicked in header
     */
    function expand_clicked(id: number) {
        categoryid.value = id;
        table_update();
    }
</script>
