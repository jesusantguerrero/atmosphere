import { defineStore } from "pinia";
import { computed, reactive, ref } from "vue";

export interface AssistantSection {
    name: string;
    label: string;
    title: string;
    container: "top" | "bottom";
    disabled?: boolean;
    component?: any;
    icon?: any;
    hideMargin?: boolean;
}

export enum AssistantTools {
  SpellCheck = "grammar",
  Search = "text-search",
  FileComparison = "file_comparison",
  Chat = "chat",
}


export interface HighlightRecord {
  text: string;
}
export interface WidgetContext {
    tenantId: string;
    isExternal: boolean;
    lang: string;
    session: string;
    url: string;
    hostname: string;
    endpoint: string;
    token: string
    visorUrl: string;
    featureFlags: {
      hasAssistantEnabled: boolean;
    };
    filesMetadata: {
      url: string;
      filename: string;
      alias: string;
    }[]
    theme: Record<string, string>;
}

export const useApplicationStore = defineStore('application', () => {

    const selectedSection = ref<AssistantSection | null>(null);
    const selectedTool = ref<AssistantSection | null>(null);

    const WidgetState = reactive<WidgetContext>({
      tenantId: "",
      isExternal: false,
      lang: "es",
      session: "",
      url: "",
      endpoint: "",
      token: "",
      hostname: "",
      visorUrl: "",
      filesMetadata: [],
      featureFlags: {
        hasAssistantEnabled: false,
      },
      theme: {},
    });


    const FileBState = reactive({
      file: "",
      fileData: ""
    });

    const setContext = (data: WidgetContext) => {
      WidgetState.featureFlags.hasAssistantEnabled = data.featureFlags.hasAssistantEnabled;
      WidgetState.hostname = data.hostname;
      WidgetState.isExternal = data.isExternal;
      WidgetState.lang = data.lang;
      WidgetState.session = data.session;
      WidgetState.tenantId = data.tenantId;
      WidgetState.url = data.url;
      WidgetState.theme = data.theme;
      WidgetState.endpoint = data.endpoint
      WidgetState.token = data.token
      WidgetState.visorUrl = data.visorUrl
      WidgetState.filesMetadata = data.filesMetadata
    }

    const fetchFile = async (filename: string = "new file", url: string, alias?: string) => {
      const visorHost = WidgetState.visorUrl || location.origin;
      const params = new URLSearchParams({
        asset: 'download_file',
        url: url,
        session: WidgetState.session
      })
      const response = await fetch(`${visorHost}/portal.cgi?${params.toString()}`).then(res => res.blob())
      const localFile = new File([response], filename, {
        type: response.type,
      });

      return localFile
    }

    const onCloseWidget = () => {

    }

    const onWidgetToolChanged = (widgetToolName: string) => {

    }

    const fileAlias = computed(() => {
      return WidgetState.filesMetadata?.at(0)?.alias ?? ""
    })

    return {
        selectedSection,
        selectedTool,
        WidgetState,
        FileBState,
        fileAlias,
        setContext,
        fetchFile,
        onCloseWidget,
        onWidgetToolChanged
    }
})
