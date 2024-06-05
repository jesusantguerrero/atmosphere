import { formatDate } from '@/utils/index';
import { formatMoney } from '@/utils/formatMoney';
import { IClient } from '@/Modules/clients/clientEntity';
import { h } from "vue";
import { ElAvatar, ElTag } from "element-plus"
// @ts-ignore
import { differenceInCalendarDays, parseISO } from 'date-fns';
import { Link } from '@inertiajs/vue3';

interface IRent {
    client: IClient,
}

export default [
    {
      name: 'display_name',
      label: 'Plan',
      align: 'left',
      minWidth: 150,
      render(row: Record<string, any>) {
          return h(Link, { href: `/admin/teams/${row.id}` }, row.plan?.display_name )
      }
    },
    {
        name: 'client',
        label: 'User',
        class: "text-center",
        headerClass: "text-center",
        minWidth: 150,
        render(row: IRent) {
            const initials = row.biller?.name?.split(' ').map((name: string) => name?.at?.(0)).join('');

            return h('div', { class: 'px-4' }, [
              h('div', { class: 'flex items-center space-x-2' }, [
                h(ElAvatar, { shape: 'circle' }, initials),
                h('span', { class: 'text-xs'}, row?.biller?.name)
              ]),
          ]);
        }
    },
    {
            name: 'id',
            label: 'Plan Details',
            class: "text-left",
            headerClass: "text-center",
            align: 'left',
            minWidth: 200,
            render(row: Record<any, any>) {

              const lateFee = row.commission > 100 ? parseFloat(row.commission ?? 0).toFixed(2) : `${parseFloat(row.commission ?? 0)?.toFixed?.(2)}%`
              return h('div', [
                h('p', {class: 'font-bold' }, formatMoney(row.plan?.quantity ?? 0, 'USD')),
                h('p', `Duración: ${formatDate(row.created_at)} - ${formatDate(row.ends_at)} | Mora: ${lateFee}`),
              ])
            }
    },{
        name: 'owner_name',
        class: "text-center",
        type: 'money',
        headerClass: "text-center",
        label: 'Team'
    }, {
        name: 'days',
        label: 'Next payment',
        render(row: any) {
            let daysLeft = null;
            if (row.next_billing_date) {
              daysLeft = differenceInCalendarDays(parseISO(row.next_billing_date), new Date())
            }
            return h(ElTag, { type: 'primary' }, daysLeft)
        }
    }, {
        name: 'status',
        label: 'Estado',
        render(row) {
            return h(ElTag, { type: 'primary' }, 'status')
        }
    },
    {
        name: 'actions',
        label: 'Acciones',
        minWidth: 150,
    }
]
