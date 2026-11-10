import { Alert } from "@javaabu/forms";
import { StencilForm } from "@javaabu/stencil";
import { SettingsNav } from "../../components/SettingsNav";
import type { SettingsEditProps } from "../../types";

/** settings::Settings/Edit — one settings page and its form */
export default function Edit({ form, page, pages, status, strings }: SettingsEditProps) {
  return (
    <div className="flex flex-col gap-6" data-settings-page={page.key}>
      <div className="flex flex-col gap-1">
        <h1 className="text-2xl font-semibold">{strings.title}</h1>
        {page.description ? <p className="text-muted-foreground">{page.description}</p> : null}
      </div>

      <SettingsNav pages={pages} label={strings.pages} />

      {status ? <Alert type="success">{status}</Alert> : null}

      {page.can_update ? null : <Alert type="info">{strings.read_only}</Alert>}

      <StencilForm key={page.key} schema={form} />
    </div>
  );
}
