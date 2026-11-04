import { NavTabs } from "@javaabu/forms";
import type { SettingsPageData } from "../types";

export interface SettingsNavProps {
  pages: SettingsPageData[];
  /** The accessible name of the navigation */
  label?: string;
  className?: string;
}

/** Tabs that link to each settings page; nothing when there's only one page */
export function SettingsNav({ pages, label, className }: SettingsNavProps) {
  if (pages.length < 2) {
    return null;
  }

  return (
    <div aria-label={label} role="navigation" className={className}>
      <NavTabs
        tabs={pages.map((page) => ({
          title: page.label,
          url: page.url,
          active: page.active,
          icon: page.icon ?? undefined,
        }))}
      />
    </div>
  );
}
