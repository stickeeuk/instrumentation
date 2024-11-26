# FROM otel/opentelemetry-collector-contrib:0.113.0-amd64
FROM golang:1.22-bookworm

COPY config.yaml /etc/otelcol-contrib/config.yaml
# COPY --chmod=755 otelcontribcol_linux_amd64 /otelcol-contrib
COPY --chmod=755 otelcol-contrib /otelcol-contrib

ENTRYPOINT ["/otelcol-contrib"]
CMD ["--config", "/etc/otelcol-contrib/config.yaml"]

EXPOSE 4317/tcp 4318/tcp 55678/tcp 55679/tcp
