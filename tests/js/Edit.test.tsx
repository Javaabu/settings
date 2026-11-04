import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import Edit from "../../resources/js/Pages/Settings/Edit";
import { SettingsNav } from "../../resources/js/components/SettingsNav";
import type { SettingsEditProps } from "../../resources/js/types";
import fixture from "./fixtures/edit-props.json";

// The props SettingsController sends (written by InertiaPagesTest with UPDATE_FIXTURES=1)
const props = fixture as unknown as SettingsEditProps;

describe("settings::Settings/Edit", () => {
  it("renders the form of the page with its values", () => {
    render(<Edit {...props} />);

    expect(screen.getByRole("heading", { level: 1 })).toHaveTextContent("Settings");
    expect(screen.getByLabelText(/Site name/)).toHaveValue("ދިވެހިރާއްޖެ");
    expect(screen.getByLabelText(/Items per page/)).toHaveValue(20);
    expect(screen.getByRole("button", { name: "Save settings" })).toBeInTheDocument();
  });

  it("types Dhivehi into Thaana fields right to left", () => {
    render(<Edit {...props} />);

    const input = screen.getByLabelText(/Site name/);

    expect(input).toHaveAttribute("dir", "rtl");
    expect(input).toHaveAttribute("lang", "dv");
  });

  it("links to the other pages", () => {
    render(<Edit {...props} />);

    expect(screen.getByRole("link", { name: "Mail" })).toHaveAttribute("href", props.pages[1]?.url);
  });

  it("shows the status after saving and the read only notice", () => {
    render(
      <Edit
        {...props}
        status="ސެޓިންގްސް ސޭވްކުރެވިއްޖެ."
        page={{ ...props.page, can_update: false }}
        strings={{ ...props.strings, read_only: "Read only" }}
      />,
    );

    expect(screen.getByText("ސެޓިންގްސް ސޭވްކުރެވިއްޖެ.")).toBeInTheDocument();
    expect(screen.getByText("Read only")).toBeInTheDocument();
  });
});

describe("SettingsNav", () => {
  it("renders nothing for a single page", () => {
    const { container } = render(<SettingsNav pages={props.pages.slice(0, 1)} />);

    expect(container).toBeEmptyDOMElement();
  });
});
