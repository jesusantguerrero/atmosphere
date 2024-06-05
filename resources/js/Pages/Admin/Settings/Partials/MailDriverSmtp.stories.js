import MailDriverSmtp from './MailDriverSmtp.vue';

// More on how to set up stories at: https://storybook.js.org/docs/vue/writing-stories/introduction
export default {
  title: 'Settings/EmailConfigSmtp',
  component: MailDriverSmtp,
  tags: ['core'],
  argTypes: {},
};

export const Default = {
  args: {
    title: "Delete Organization",
  },
};
