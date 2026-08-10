'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const states = document.getElementById(
        'searchStates'
    );

    const cities = document.getElementById(
        'searchCities'
    );

    if (!states || !cities) {
        return;
    }

    const loadCities = async () => {
        const stateIds = Array.from(
            states.selectedOptions
        )
            .map((option) => option.value)
            .filter(Boolean);

        cities.innerHTML = '';

        if (stateIds.length === 0) {
            return;
        }

        const params = new URLSearchParams();

        stateIds.forEach((stateId) => {
            params.append(
                'state_ids[]',
                stateId
            );
        });

        try {
            const response = await fetch(
                `/search/cities?${params.toString()}`,
                {
                    headers: {
                        Accept: 'application/json',
                    },
                }
            );

            if (!response.ok) {
                throw new Error(
                    'City request failed.'
                );
            }

            const payload =
                await response.json();

            const rows = Array.isArray(
                payload.cities
            )
                ? payload.cities
                : [];

            rows.forEach((city) => {
                const option =
                    document.createElement(
                        'option'
                    );

                option.value =
                    String(city.id ?? '');

                option.textContent =
                    String(city.name ?? '');

                cities.appendChild(
                    option
                );
            });
        } catch (error) {
            console.error(
                'Unable to load cities.',
                error
            );
        }
    };

    states.addEventListener(
        'change',
        loadCities
    );
});