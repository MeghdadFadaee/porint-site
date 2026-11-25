const templateFields = ['garment', 'color'] as const;
const fields = [...templateFields, 'placement'] as const;
type Field = typeof fields[number];
type PreviewSelection = Record<Field, string>;

type Template = {
    name: string;
    garment: string;
    color: string;
    side: string;
    photo: string;
    width: number;
    placements: Record<string, { x: number; y: number }>;
};

type PreviewData = {
    templates: Template[];
    labels: Record<Field, Record<string, string>>;
};

function mountPreview(root: HTMLElement) {
    const dataNode = root.querySelector('[data-preview-data]');
    if (!dataNode?.textContent) return;
    const { templates, labels }: PreviewData = JSON.parse(dataNode.textContent);
    if (!templates.length) return;

    const slots = Array.from(root.querySelectorAll<HTMLElement>('[data-slot]'));
    const button = root.querySelector<HTMLButtonElement>('[data-compare]');
    const second = templates.find((template) => template.garment !== templates[0].garment) ?? templates[1];

    function bindSlot(slot: HTMLElement, initial: Template) {
        const photo = slot.querySelector<HTMLImageElement>('[data-garment-photo]')!;
        const print = slot.querySelector<HTMLImageElement>('[data-print]')!;
        const caption = slot.querySelector<HTMLElement>('[data-garment-caption]')!;
        const controls = slot.querySelector<HTMLElement>('[data-controls]')!;
        const state: PreviewSelection = {
            garment: initial.garment,
            color: initial.color,
            placement: initial.side + ':' + Object.keys(initial.placements)[0],
        };

        function setChoices(field: Field, choices: string[], labelFor = (value: string) => labels[field][value] ?? value) {
            const select = controls.querySelector<HTMLSelectElement>('[data-selection="' + field + '"]')!;
            if (!choices.includes(state[field])) state[field] = choices[0];

            const options = choices.map((value) => new Option(labelFor(value), value, false, value === state[field]));
            select.replaceChildren(...options);
            select.disabled = choices.length === 1;
        }

        function render() {
            let matches = templates;
            for (const field of templateFields) {
                setChoices(field, [...new Set(matches.map((template) => template[field]))]);
                matches = matches.filter((template) => template[field] === state[field]);
            }
            const placements = new Map(matches.flatMap((template) =>
                Object.entries(template.placements).map(([name, position]) =>
                    [template.side + ':' + name, { name, template, position }] as const),
            ));
            setChoices('placement', [...placements.keys()], (value) => {
                const name = placements.get(value)!.name;
                return labels.placement[name] ?? name;
            });
            const { template, position } = placements.get(state.placement)!;
            if (photo.getAttribute('src') !== template.photo) photo.src = template.photo;
            photo.alt = template.name;
            caption.textContent = template.name;
            print.style.setProperty('--print-width', template.width + '%');
            print.style.setProperty('--print-x', position.x + '%');
            print.style.setProperty('--print-y', position.y + '%');
        }

        controls.addEventListener('change', (event) => {
            if (!(event.target instanceof HTMLSelectElement)) return;
            const field = event.target.dataset.selection as Field;
            state[field] = event.target.value;
            render();
        });
        controls.hidden = false;
        render();
    }

    if (slots[0]) bindSlot(slots[0], templates[0]);
    if (button && slots[1] && second) {
        bindSlot(slots[1], second);
        button.hidden = false;
        button.addEventListener('click', () => {
            const comparing = slots[1].hidden === true;
            slots[1].hidden = !comparing;
            root.classList.toggle('is-compare', comparing);
            button.setAttribute('aria-pressed', String(comparing));
            button.textContent = (comparing ? button.dataset.single : button.dataset.dual) ?? '';
            slots.forEach((slot) => {
                const name = slot.querySelector<HTMLElement>('[data-slot-name]');
                if (name) name.hidden = !comparing;
            });
        });
    }
}

document.querySelectorAll<HTMLElement>('[data-preview]').forEach(mountPreview);
