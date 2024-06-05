import { IClient } from '@/Modules/clients/clientEntity';
import { h } from "vue";
import { ElTag } from "element-plus"
// @ts-ignore
import { differenceInCalendarDays, parseISO } from 'date-fns';
import { Link } from '@inertiajs/vue3';


export default [
    {
        name: 'name',
        label: 'Empresa',
        align: 'left',
        minWidth: 150,
        render(row: Record<string, any>) {
            return h(Link, { href: `/admin/teams/${row.id}` }, row.name )
        }
      },
      {
          name: 'owner',
          label: 'Contacto',
          class: "text-left",
          headerClass: "text-center",
          align: 'left',
          minWidth: 200,
          render(row: Record<string, any>) {
            return h('div', row.owner?.name)
          }
    },{
        name: 'days',
        label: 'Dias de registro',
        class: "text-center",
        align: 'center',
        render(row: Record<string, any>) {

            let daysLeft = "--"
            try {
                differenceInCalendarDays(new Date(), parseISO(row.created_at))
            } catch (err) {
                console.log(err)
            }
            return h(Link, {href: `/contacts/${row.client?.id}/tenants/rents/${row.id}/renew`} , h(ElTag, { type: 'primary' }, daysLeft))
        }
    },{
        name: 'app_profile_name',
        label: 'App',
        class: "text-center",
        align: 'center'
    },
    {
        name: 'actions',
        label: 'Acciones',
        minWidth: 150,
    }
]
