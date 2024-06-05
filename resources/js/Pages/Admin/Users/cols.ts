import { formatDate } from '@/utils/index';
import { IClient } from '@/Modules/clients/clientEntity';
import { h } from "vue";
import { ElTag } from "element-plus"
// @ts-ignore
import { differenceInCalendarDays, parseISO } from 'date-fns';
import { Link } from '@inertiajs/vue3';

interface IRent {
    client: IClient,
}

export default [
    {
        name: 'name',
        label: 'Name',
        align: 'left',
        minWidth: 150,
        render(row: Record<string, any>) {
            return h(Link, { href: `/admin/teams/${row.id}` }, `${row.name}<${row.email}>` )
        }
      },
      {
          name: 'last_login_at',
          label: 'Last login',
          class: "text-left",
          headerClass: "text-center",
          align: 'left',
          minWidth: 200,
          render(row: Record<string, any>) {
            return h('div', formatDate(row.last_login_at))
          }
    },{
        name: 'days',
        label: 'Dias de registro',
        class: "text-center",
        align: 'center',
        render(row: Record<string, string>) {
            let daysLeft = row.created_at;
            try {
                 daysLeft =  differenceInCalendarDays(new Date(), parseISO(row.created_at ?? new Date()))
            } catch(e){
                console.log(daysLeft, e)
            }
            return h(Link, {href: `/contacts/${row.client?.id}/tenants/rents/${row.id}/renew`} , h(ElTag, { type: 'primary' }, daysLeft))
        }
    },
    {
        name: 'actions',
        label: 'Acciones',
        minWidth: 150,
    }
]
