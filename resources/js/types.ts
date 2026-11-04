import type { StencilSchema } from "@javaabu/stencil";

/** A settings page, as SettingsController sends it */
export interface SettingsPageData {
  key: string;
  label: string;
  description: string | null;
  icon: string | null;
  url: string;
  can_update: boolean;
  /** Only in `pages`: whether it's the page being shown */
  active?: boolean;
}

export interface SettingsStrings {
  title: string;
  read_only: string;
  pages: string;
}

/** The props of settings::Settings/Edit */
export interface SettingsEditProps {
  /** The page's stencil form */
  form: StencilSchema;
  page: SettingsPageData;
  /** Every page the user can see, for navigation */
  pages: SettingsPageData[];
  /** The message after saving */
  status: string | null;
  strings: SettingsStrings;
}
